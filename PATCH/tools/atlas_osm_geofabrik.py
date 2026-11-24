#!/usr/bin/env python3
"""HN BLOOM ATLAS: offline Geofabrik OSM POI shapefile to CRM CSV.

No external Python packages. Reads both .shp and .dbf directly from the public
Geofabrik archive without unpacking 0.5 GB+ to disk. No outreach or CRM writes.
"""
import argparse
import collections
import csv
import math
import os
from pathlib import Path
import struct
import sys
import tempfile
import urllib.request
import zipfile

SOURCE_URL = 'https://download.geofabrik.de/africa/morocco-latest-free.shp.zip'
SOURCE_ID = 'geofabrik_osm'
# Locations are approximate city centers; the underlying OSM POIs may be inaccurate.
CITIES = {
    'Casablanca': (33.5731, -7.5898), 'Rabat': (34.0209, -6.8416),
    'Marrakech': (31.6295, -7.9811), 'Tanger': (35.7595, -5.8340),
    'Fes': (34.0640, -4.9730), 'Agadir': (30.4278, -9.5981),
    'Meknes': (33.8935, -5.5473), 'Oujda': (34.6814, -1.9086),
    'Kenitra': (34.2610, -6.5802), 'Tetouan': (35.5785, -5.3684),
}
# End-customer facilities, not automatically assumed to be ready-to-buy leads.
FACILITY_CLASSES = {'hotel', 'motel', 'guesthouse', 'hostel', 'hospital', 'clinic',
                    'school', 'college', 'university', 'kindergarten',
                    'supermarket', 'mall', 'department_store', 'commercial',
                    'industrial', 'bank', 'office', 'company', 'camp_site'}
SECURITY_WORDS = ('securite', 'sécurité', 'security', 'domotique', 'surveillance',
                  'camera', 'caméra', 'alarme', 'videoprotection',
                  'vidéosurveillance', 'controle d acces', "contrôle d'accès")
HEADERS = ['company', 'city', 'category', 'phone', 'email', 'website', 'source_url', 'id']


def distance_km(lat1, lon1, lat2, lon2):
    x1, x2 = math.radians(lat1), math.radians(lat2)
    dlat, dlon = x2-x1, math.radians(lon2-lon1)
    a = math.sin(dlat/2)**2 + math.cos(x1)*math.cos(x2)*math.sin(dlon/2)**2
    return 6371.0 * 2 * math.asin(math.sqrt(min(a,1.0)))


def location(lat, lon, radius):
    options = [(distance_km(lat, lon, a, b), name) for name, (a,b) in CITIES.items()]
    dist, name = min(options)
    return name if dist <= radius else None


def get_layers(z):
    names = z.namelist()
    def pick(ext):
        matches = [n for n in names if n.lower().endswith('gis_osm_pois_free_1.'+ext)]
        if not matches:
            raise ValueError('Archive sans couche gis_osm_pois_free_1.'+ext)
        return matches[0]
    return pick('dbf'), pick('shp')


def dbf_rows(stream):
    header = stream.read(32)
    if len(header) != 32:
        raise ValueError('DBF header invalide')
    _, _, _, _, total, header_len, rec_len = struct.unpack('<BBBBIHH', header[:12])
    if not 32 <= header_len <= 65535 or not 2 <= rec_len <= 65535:
        raise ValueError('DBF header dimensions invalides')
    descriptors = stream.read(header_len-32)
    fields, cursor = [], 1
    for start in range(0, len(descriptors)-1, 32):
        desc = descriptors[start:start+32]
        if not desc or desc[0] == 0x0D: break
        name = desc[:11].split(b'\x00')[0].decode('ascii','ignore').lower()
        width = desc[16]
        fields.append((name, cursor, cursor+width))
        cursor += width
    if cursor > rec_len:
        raise ValueError('Champs DBF incompatibles')
    for _ in range(total):
        raw = stream.read(rec_len)
        if len(raw) != rec_len: raise ValueError('DBF tronque')
        if raw[0] == 0x2A:
            yield None
            continue
        yield {k: raw[a:b].decode('utf-8','replace').strip(' \x00') for k,a,b in fields}


def shape_points(stream):
    if len(stream.read(100)) != 100:
        raise ValueError('SHP header invalide')
    while True:
        header = stream.read(8)
        if not header: return
        if len(header) < 8: raise ValueError('SHP record header tronque')
        _, words = struct.unpack('>II', header)
        size = words * 2
        if size < 4 or size > 10000000: raise ValueError('SHP record size invalide')
        body = stream.read(size)
        if len(body) != size: raise ValueError('SHP tronque')
        shape_type = struct.unpack('<I', body[:4])[0]
        if shape_type == 1 and size >= 20:
            lon, lat = struct.unpack('<dd', body[4:20])
            yield (lat, lon) if -90 <= lat <= 90 and -180 <= lon <= 180 else None
        else:
            yield None


def acceptable(name, klass):
    if not name or len(name) < 3 or len(name) > 255 or '\ufffd' in name:
        return None
    lower = name.casefold()
    if any(term in lower for term in SECURITY_WORDS):
        return 'installateur'  # Candidate category only; must be reviewed.
    if klass.lower() in FACILITY_CLASSES:
        return 'client_final'
    return None


def extract(archive, output, *, per_city=40, radius=20):
    buckets = collections.defaultdict(list)
    counts = collections.Counter()
    dedup = set()
    with zipfile.ZipFile(archive) as z:
        dbf, shp = get_layers(z)
        with z.open(dbf) as d, z.open(shp) as s:
            import itertools
            for row, coords in itertools.zip_longest(dbf_rows(d), shape_points(s)):
                if row is None or coords is None: continue
                name = row.get('name','').strip()
                kind = acceptable(name, row.get('fclass',''))
                if not kind: continue
                lat, lon = coords
                city = location(lat, lon, radius)
                if not city or len(buckets[city]) >= per_city: continue
                osm_id = row.get('osm_id','').strip()
                if not osm_id or not osm_id.isdigit(): continue
                key = (city, osm_id, name.casefold())
                if key in dedup: continue
                dedup.add(key)
                # Public OSM map pin is evidence of POI location, not verified contact.
                map_url = 'https://www.openstreetmap.org/?mlat={:.6f}&mlon={:.6f}#map=17/{:.6f}/{:.6f}'.format(lat, lon, lat, lon)
                buckets[city].append({
                    'company': name, 'city': city, 'category': kind,
                    'phone':'', 'email':'', 'website':'', 'source_url':map_url,
                    'id': 'poi_{}_{}'.format(osm_id, str(round(lat,5)).replace('.','_')+'_'+str(round(lon,5)).replace('.','_')),
                })
                counts[kind] += 1
    output.parent.mkdir(parents=True, exist_ok=True)
    with output.open('w', encoding='utf-8-sig', newline='') as stream:
        writer = csv.DictWriter(stream, fieldnames=HEADERS)
        writer.writeheader()
        for city in CITIES:
            writer.writerows(buckets[city])
    return {'total':sum(counts.values()), 'categories':dict(counts), 'cities': {k:len(v) for k,v in buckets.items()}}


def download(url, destination):
    destination.parent.mkdir(parents=True,exist_ok=True)
    partial = destination.with_name(destination.name+'.part')
    request = urllib.request.Request(url,headers={'User-Agent':'HNBloomAtlas/1.0 (OSM data download; business research)'})
    print('Telechargement de Geofabrik (environ 500 MB, une seule fois)...',flush=True)
    try:
        with urllib.request.urlopen(request,timeout=120) as response, open(partial,'wb') as out:
            if response.geturl().split('/')[2] not in {'download.geofabrik.de','download-ext2.geofabrik.de','download-ext1.geofabrik.de'}:
                raise ValueError('Redirection hors des serveurs Geofabrik')
            while True:
                chunk = response.read(1024*1024)
                if not chunk: break
                out.write(chunk)
        if not zipfile.is_zipfile(partial): raise ValueError('Telechargement incomplet / archive invalide')
        os.replace(partial,destination)
    finally:
        if partial.exists(): partial.unlink()


def main():
    p=argparse.ArgumentParser(description='ATLAS offline OSM POI extraction for HN BLOOM CRM')
    p.add_argument('--archive',type=Path,help='Geofabrik downloaded ZIP; download if missing')
    p.add_argument('--output',type=Path,required=True,help='CSV for CRM import')
    p.add_argument('--download', action='store_true', help='Fetch ~500MB Geofabrik data if missing')
    p.add_argument('--per-city',type=int,default=40)
    p.add_argument('--radius-km',type=float,default=20)
    a=p.parse_args()
    if not 1 <= a.per_city <= 100 or not 1 <= a.radius_km <= 50:
        p.error('per-city 1..100 et radius 1..50')
    archive = a.archive or (a.output.parent / 'morocco-latest-free.shp.zip')
    try:
        if not archive.exists():
            if not a.download: p.error('Archive absente: utilisez --download pour la recuperer')
            download(SOURCE_URL,archive)
        result = extract(archive, a.output, per_city=a.per_city, radius=a.radius_km)
        print('ATLAS OSM | {}'.format(result),flush=True)
        print('CSV: {}'.format(a.output),flush=True)
        print('Aucune donnee de contact inventee. Prospects a verifier avant contact.',flush=True)
        return 0
    except (OSError, ValueError, zipfile.BadZipFile, Exception) as e:
        print('ATLAS ERROR: {}'.format(e),file=sys.stderr)
        return 1

if __name__ == '__main__': sys.exit(main())
