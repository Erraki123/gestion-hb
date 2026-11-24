#!/usr/bin/env python3
"""ATLAS 6L - offline OSM contact evidence from a local Morocco .osm.pbf.
No calls to APIs, no CRM updates, no sending messages. OSM tags require review.
"""
import argparse
import collections
import csv
import json
import re
import sys
import unicodedata
from pathlib import Path
from urllib.parse import urlsplit

REQUIRED = ('company','city','category','phone','email','website','source_url','id')
EVIDENCE = ('company','city','phone','email','website','evidence_url')
ID_RE = re.compile(r'^poi_(\d+)_')
CONTACT_TAGS = {'phone': ('contact:phone', 'phone'), 'email': ('contact:email','email'), 'website': ('contact:website','website')}

def norm(value):
    s = unicodedata.normalize('NFKD', str(value or '').casefold())
    return ' '.join(re.findall('[a-z0-9]+', ''.join(c for c in s if not unicodedata.combining(c))))

def valid_contact(field, value):
    if not value or '\n' in value or '\r' in value or ';' in value: return False
    if field == 'phone':
        return bool(re.fullmatch(r'\+?[0-9() .\-]{8,25}', value)) and 9 <= len(re.sub(r'\D', '', value)) <= 15
    if field == 'email':
        return bool(re.fullmatch(r'[^\s@<>]+@[^\s@<>]+\.[A-Za-z]{2,}',value)) and len(value) <= 254
    if field == 'website':
        try:
            parsed = urlsplit(value)
            return parsed.scheme == 'https' and bool(parsed.hostname) and '.' in parsed.hostname and not parsed.username and not parsed.password and not parsed.fragment
        except ValueError: return False
    return False

def read_candidates(path):
    with path.open('r', encoding='utf-8-sig', newline='') as handle:
        reader = csv.DictReader(handle)
        if not reader.fieldnames or not set(REQUIRED).issubset(reader.fieldnames):
            raise ValueError('Candidate CSV missing required columns')
        records = list(reader)
    if len(records) > 300: raise ValueError('Maximum 300 candidates per run')
    return records

def get_tag(tags, keys):
    """Tag dictionary (OSM may contain several representations)."""
    for k in keys:
        candidate = str(tags.get(k) or '').strip()
        if candidate: return candidate
    return ''

def read_evidence(candidate, obj_type, osm_id, tags):
    tag_name = get_tag(tags, ('name','name:fr','name:ar'))
    if norm(tag_name) != norm(candidate['company']):
        return None
    values = {}
    for field, keys in CONTACT_TAGS.items():
        raw = get_tag(tags, keys)
        values[field] = raw if valid_contact(field, raw) else ''
    if not any(values.values()): return None
    return {**{k:candidate[k] for k in ('company','city')}, **values,
            'evidence_url':f'https://www.openstreetmap.org/{obj_type}/{osm_id}'}

def collect(records, objects):
    """In-memory processing used for tests. objects: iterable (type, id, tags)."""
    target = collections.defaultdict(list)
    for idx, r in enumerate(records):
        match = ID_RE.match(r.get('id',''))
        if match: target[int(match.group(1))].append((idx,r))
    evidence=collections.defaultdict(list)
    for kind, osm_id, tags in objects:
        if kind not in ('node','way','relation'): continue
        for idx, r in target.get(int(osm_id), []):
            row = read_evidence(r,kind,osm_id,tags)
            if row: evidence[idx].append(row)
    return finalize(records,evidence)

def finalize(records,evidence):
    rows=[]; ambiguous=0
    for idx, _ in enumerate(records):
        hits=evidence.get(idx,[])
        # Multiple object types can share a numeric ID; do not assume they are same POI.
        if len(hits)==1: rows.append(hits[0])
        elif len(hits)>1: ambiguous+=1
    return rows,ambiguous

def scan_pbf(pbf, records):
    try: import osmium
    except ImportError as ex:
        raise RuntimeError('Python module osmium missing; install using: python -m pip install osmium') from ex
    target=collections.defaultdict(list)
    for idx,r in enumerate(records):
        match=ID_RE.match(r.get('id',''))
        if match: target[int(match.group(1))].append((idx,r))
    hits=collections.defaultdict(list)
    class Handler(osmium.SimpleHandler):
        def process(self,kind,obj):
            candidates = target.get(obj.id)
            if not candidates: return
            tags={t.k:t.v for t in obj.tags}
            for idx,r in candidates:
                result=read_evidence(r,kind,obj.id,tags)
                if result: hits[idx].append(result)
        def node(self,obj): self.process('node',obj)
        def way(self,obj): self.process('way',obj)
        def relation(self,obj): self.process('relation',obj)
    Handler().apply_file(str(pbf),locations=False)
    return finalize(records,hits)

def main():
    parser=argparse.ArgumentParser(description='Extract evidence only from an existing local OSM PBF file')
    parser.add_argument('--pbf',type=Path,required=True)
    parser.add_argument('--candidates',type=Path,default=Path('storage/app/prospect-imports/atlas-smart-preselection.csv'))
    parser.add_argument('--output',type=Path,default=Path('storage/app/prospect-imports/atlas-contact-evidence.csv'))
    a=parser.parse_args()
    if not a.pbf.is_file(): parser.error('PBF not found: '+str(a.pbf))
    if not a.candidates.is_file(): parser.error('Candidates not found: '+str(a.candidates))
    if a.output.resolve()==a.candidates.resolve(): parser.error('Output cannot overwrite candidates')
    try:
        records=read_candidates(a.candidates)
        rows,ambiguous=scan_pbf(a.pbf,records)
        a.output.parent.mkdir(parents=True,exist_ok=True)
        # Avoid overwriting a manually maintained evidence file.
        if a.output.exists():
            parser.error('Evidence output exists; choose another --output to preserve it')
        with a.output.open('w',encoding='utf-8-sig',newline='') as handle:
            writer=csv.DictWriter(handle,fieldnames=EVIDENCE)
            writer.writeheader();writer.writerows(rows)
        print(json.dumps({'candidates':len(records),'contact_evidence':len(rows),'ambiguous_skipped':ambiguous,
                          'output':str(a.output),'db_writes':0,'messages_sent':0,
                          'note':'OSM contacts are unverified public tags; manual review required.'},ensure_ascii=False,indent=2))
    except (ValueError, OSError, RuntimeError, csv.Error) as ex:
        print('ATLAS ERROR:',ex,file=sys.stderr);sys.exit(1)

if __name__=='__main__': main()
