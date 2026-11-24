#!/usr/bin/env python3
"""ATLAS 6R: explicit Geofabrik metadata check and staged, review-only snapshot.

No CRM writes, no scheduled task changes, no messaging. A stage is never promoted.
"""
import argparse
import hashlib
import json
import os
import sys
import urllib.request
import zipfile
from datetime import datetime, timezone
from email.utils import parsedate_to_datetime
from pathlib import Path
from urllib.parse import urlsplit

URL = 'https://download.geofabrik.de/africa/morocco-latest-free.shp.zip'
ALLOWED_HOSTS = {'download.geofabrik.de', 'download-ext1.geofabrik.de', 'download-ext2.geofabrik.de'}
MAX_BYTES = 800 * 1024 * 1024


def now():
    return datetime.now(timezone.utc).isoformat()


def write_json(path, payload):
    path.parent.mkdir(parents=True, exist_ok=True)
    temp = path.with_name(path.name + '.tmp')
    temp.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding='utf-8')
    os.replace(temp, path)


def validate_url(url):
    u = urlsplit(url)
    if u.scheme != 'https' or u.hostname not in ALLOWED_HOSTS:
        raise ValueError('Refused unexpected download host: '+str(u.hostname))


def inspect_remote(opener=urllib.request.urlopen):
    request = urllib.request.Request(URL, method='HEAD', headers={'User-Agent':'HNBloomATLAS/1.0 (local source update check)'})
    with opener(request, timeout=30) as resp:
        validate_url(resp.geturl())
        headers = resp.headers
        modified = headers.get('Last-Modified')
        dt = parsedate_to_datetime(modified).astimezone(timezone.utc) if modified else None
        length = int(headers.get('Content-Length') or '0')
        if length < 0 or length > MAX_BYTES:
            raise ValueError('Unexpected content length')
        return {'last_modified_utc':dt.isoformat() if dt else None,
                'content_length':length or None,
                'etag':headers.get('ETag')}


def status(project, remote):
    folder = project/'storage'/'app'/'prospect-imports'
    old = folder/'morocco-latest-free.shp.zip'
    meta = folder/'atlas-source-observed.json'
    previous = json.loads(meta.read_text(encoding='utf-8')) if meta.is_file() else None
    changed = None
    if previous:
        if remote.get('etag') and previous.get('etag'):
            changed = remote['etag'] != previous['etag']
        elif remote.get('last_modified_utc') and previous.get('last_modified_utc'):
            changed = remote['last_modified_utc'] != previous['last_modified_utc']
    result = {'status':'ok','mode':'source_check','checked_at_utc':now(),
              'local_archive_exists':old.is_file(), 'source_updated_since_previous_check':changed,
              'remote':remote, 'previous_observation':bool(previous),
              'note':'Metadata change does not establish new leads; no download or CRM write.'}
    # Observations are metadata-only. This baseline never claims the local file matches the remote bytes.
    write_json(meta, {'checked_at_utc':result['checked_at_utc'], **remote})
    return result


def stage(project, remote, opener=urllib.request.urlopen):
    folder = project/'storage'/'app'/'prospect-imports'
    if not (project/'artisan').exists():
        raise ValueError('Not in Laravel project directory')
    script = project/'tools'/'atlas_osm_geofabrik.py'
    if not script.exists():
        raise ValueError('Phase 6E extractor not found')
    staging = folder/'atlas-refresh-preview'
    archive = staging/'morocco-refresh.shp.zip'
    csv = staging/'atlas-osm-refresh.csv'
    staging.mkdir(parents=True,exist_ok=True)
    validate_url(URL)
    req = urllib.request.Request(URL,headers={'User-Agent':'HNBloomATLAS/1.0 (local staged preview)'})
    part = archive.with_suffix(archive.suffix+'.part')
    downloaded = 0
    digest = hashlib.sha256()
    try:
        with opener(req,timeout=120) as response:
            validate_url(response.geturl())
            typ = str(response.headers.get('Content-Type') or '')
            if 'text/html' in typ.lower():
                raise ValueError('Unexpected HTML response')
            expected = int(response.headers.get('Content-Length') or '0')
            if expected > MAX_BYTES:
                raise ValueError('Archive exceeds safe size limit')
            with part.open('wb') as out:
                while True:
                    chunk=response.read(2*1024*1024)
                    if not chunk: break
                    downloaded+=len(chunk)
                    if downloaded>MAX_BYTES:
                        raise ValueError('Archive exceeds safe size limit')
                    out.write(chunk)
                    digest.update(chunk)
                    if downloaded % (40*1024*1024) < len(chunk):
                        print('Preview download: {:.1f} MB'.format(downloaded/1048576),flush=True)
        if expected and downloaded != expected:
            raise ValueError('Truncated download')
        if downloaded < 1024*1024 or not zipfile.is_zipfile(part):
            raise ValueError('Invalid Geofabrik archive')
        with zipfile.ZipFile(part) as z:
            names = z.namelist()
            if not any(x.endswith('gis_osm_pois_free_1.shp') for x in names):
                raise ValueError('Expected OSM POI shapefile missing')
            if not any(x.endswith('gis_osm_pois_free_1.dbf') for x in names):
                raise ValueError('Expected OSM POI database missing')
        os.replace(part,archive)
    finally:
        if part.exists():part.unlink()
    sys.path.insert(0,str(script.parent))
    from atlas_osm_geofabrik import extract
    count=extract(archive,csv,per_city=40,radius=20)
    report={'status':'staged','mode':'preview_only','created_at_utc':now(),
            'snapshot_sha256':digest.hexdigest(), 'bytes_downloaded':downloaded,
            'remote_metadata':remote, 'extracted':count,
            'archive':str(archive.relative_to(project)), 'preview_csv':str(csv.relative_to(project)),
            'crm_writes':0,'messages_sent':0,
            'warning':'New CSV is in isolated staging only. Existing production CSV and contact decisions were not changed.'}
    write_json(staging/'atlas-refresh-report.json',report)
    return report


def main():
    p=argparse.ArgumentParser()
    p.add_argument('--project',type=Path,default=Path.cwd())
    opts=p.add_mutually_exclusive_group(required=True)
    opts.add_argument('--check',action='store_true',help='Lightweight remote HEAD check (network)')
    opts.add_argument('--stage',action='store_true',help='Download a new 470 MB snapshot into isolated preview folder')
    args=p.parse_args()
    project=args.project.resolve()
    if not (project/'artisan').exists():
        print('ERROR: Laravel project root missing artisan',file=sys.stderr);return 2
    try:
        remote=inspect_remote()
        if args.check:
            result=status(project,remote)
        else:
            result=stage(project,remote)
        print(json.dumps(result,indent=2,ensure_ascii=False))
        return 0
    except Exception as e:
        print('ATLAS SOURCE ERROR:',str(e),file=sys.stderr)
        return 1

if __name__=='__main__':
    raise SystemExit(main())
