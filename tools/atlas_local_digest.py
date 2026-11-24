#!/usr/bin/env python3
"""Local-only ATLAS digest; no DB access, network or outreach."""
import argparse
import csv
import hashlib
import json
import os
import tempfile
from collections import Counter
from datetime import datetime, timezone
from pathlib import Path

FIELDS = ('company','city','category','phone','email','website','source_url','id')

def load(path):
    with path.open(encoding='utf-8-sig',newline='') as f:
        reader=csv.DictReader(f)
        if not reader.fieldnames or not {'company','city'}.issubset(reader.fieldnames):
            raise ValueError('Invalid candidates CSV columns')
        return list(reader)

def key(row):
    sid=(row.get('id') or '').strip().casefold()
    if sid: return 'id:'+sid
    return 'namecity:'+(row.get('company') or '').strip().casefold()+'|'+(row.get('city') or '').strip().casefold()

def digest(rows):
    return {key(row): hashlib.sha256(json.dumps({k:row.get(k,'') for k in FIELDS}, sort_keys=True,ensure_ascii=False).encode('utf-8')).hexdigest() for row in rows}

def run(candidates,contacts,state,report):
    rows=load(candidates)
    current=digest(rows)
    if state.is_file():
        prior=json.loads(state.read_text(encoding='utf-8'))
        prev=prior.get('fingerprints',{})
        if not isinstance(prev,dict): raise ValueError('Invalid state file')
        first=False
    else:
        prev={}
        first=True
    statuses=Counter()
    if contacts.is_file():
        with contacts.open(encoding='utf-8-sig',newline='') as f:
            reader=csv.DictReader(f)
            if 'contact_status' not in (reader.fieldnames or []): raise ValueError('Contact status missing')
            for row in reader: statuses[row.get('contact_status') or 'unknown']+=1
    info={
        'generated_at_utc':datetime.now(timezone.utc).isoformat(),
        'source':'local CSV snapshot', 'first_run':first,
        'candidates':len(rows),
        'new_since_last_run':sum(k not in prev for k in current),
        'changed_since_last_run':sum(k in prev and v!=prev[k] for k,v in current.items()),
        'not_in_current_selection':sum(k not in current for k in prev),
        'contacts_by_status':dict(statuses),
        'warning':'This only monitors local CSV files. No fresh Internet discovery, AI inference, contact verification, database writes or outbound messages.'
    }
    state.parent.mkdir(parents=True,exist_ok=True)
    report.parent.mkdir(parents=True,exist_ok=True)
    # save report first, then state to avoid advancing state when report write fails
    atomic_json(report,info)
    atomic_json(state,{'fingerprints':current})
    return info

def atomic_json(path,value):
    fd,tmp=tempfile.mkstemp(prefix='.atlas-',suffix='.tmp',dir=str(path.parent))
    try:
        with os.fdopen(fd,'w',encoding='utf-8') as f:
            json.dump(value,f,ensure_ascii=False,indent=2)
        os.replace(tmp,path)
    finally:
        if os.path.exists(tmp): os.unlink(tmp)

def main():
    ap=argparse.ArgumentParser()
    base=Path('storage/app/prospect-imports')
    ap.add_argument('--candidates',type=Path,default=base/'atlas-smart-preselection.csv')
    ap.add_argument('--contacts',type=Path,default=base/'atlas-contact-review.csv')
    ap.add_argument('--state',type=Path,default=base/'atlas-local-monitor-state.json')
    ap.add_argument('--report',type=Path,default=base/'atlas-local-digest.json')
    args=ap.parse_args()
    if not args.candidates.is_file(): ap.error('Candidates CSV missing; run Smart Selection first')
    print(json.dumps(run(args.candidates,args.contacts,args.state,args.report),ensure_ascii=False,indent=2))

if __name__=='__main__': main()
