#!/usr/bin/env python3
"""HN BLOOM ATLAS 6K: deterministic and auditable contact evidence matching.

Never fabricates contacts. Never writes to DB or calls the internet.
Accepts an authorized contacts CSV from an independent source and creates a review file.
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

BASE_FIELDS = ('company','city','category','phone','email','website','source_url','id')
EVIDENCE_FIELDS = ('company','city','phone','email','website','evidence_url')
OUTPUT_EXTRA = ('contact_status','contact_evidence_url','contact_note')

def normalize(text):
    v = unicodedata.normalize('NFKD', str(text or '').casefold())
    v = ''.join(c for c in v if not unicodedata.combining(c))
    return ' '.join(re.findall(r'[a-z0-9]+', v))

def source_url_ok(value):
    try:
        u = urlsplit((value or '').strip())
        return u.scheme == 'https' and bool(u.hostname) and '.' in u.hostname and not u.username and not u.password
    except ValueError:
        return False

def website_ok(value):
    return source_url_ok(value)

def phone_ok(value):
    s = (value or '').strip()
    if not re.fullmatch(r'\+?[\d () .-]{8,25}',s): return False
    return 9 <= len(re.sub(r'\D','',s)) <= 15

def email_ok(value):
    s = (value or '').strip()
    return bool(re.fullmatch(r'[^\s@<>]+@[^\s@<>]+\.[A-Za-z]{2,}',s)) and len(s) <= 254

def read_csv(path, required):
    with path.open(encoding='utf-8-sig',newline='') as f:
        rd = csv.DictReader(f)
        if not rd.fieldnames or not set(required).issubset(rd.fieldnames):
            raise ValueError('Colonnes manquantes: '+', '.join(sorted(set(required)-set(rd.fieldnames or []))))
        return list(rd)

def match(candidates, evidences):
    index = collections.defaultdict(list)
    for e in evidences:
        index[(normalize(e['company']), normalize(e['city']))].append(e)
    names = collections.Counter((normalize(c['company']),normalize(c['city'])) for c in candidates)
    output=[]; counts=collections.Counter()
    for c in candidates:
        item = {k:c.get(k,'') for k in BASE_FIELDS}
        item.update({'contact_status':'missing', 'contact_evidence_url':'', 'contact_note':'No independent evidence matching company and city'})
        key = (normalize(c['company']),normalize(c['city']))
        matches = index.get(key,[])
        if names[key] > 1:
            item.update(contact_status='ambiguous',contact_note='Several CRM candidates share this company and city; review branch identity')
        elif len(matches) > 1:
            item.update(contact_status='ambiguous', contact_note='Several evidence rows for same company and city')
        elif len(matches) == 1:
            e=matches[0]
            url=(e.get('evidence_url') or '').strip()
            if not source_url_ok(url):
                item.update(contact_status='rejected',contact_note='Evidence must have a valid HTTPS URL')
            else:
                fields={}
                invalid=[]
                for f,validator in [('phone',phone_ok),('email',email_ok),('website',website_ok)]:
                    value=(e.get(f) or '').strip()
                    if value and not validator(value): invalid.append(f)
                    elif value and not (item.get(f) or '').strip(): fields[f]=value
                if invalid:
                    item.update(contact_status='rejected',contact_note='Invalid contact field(s): '+', '.join(invalid))
                elif not any((e.get(f) or '').strip() for f in ('phone','email','website')):
                    item.update(contact_status='missing',contact_note='Evidence has no contact details')
                elif not fields:
                    item.update(contact_status='already_present',contact_note='Existing contact fields preserved; review differences manually')
                else:
                    item.update(fields)
                    item.update(contact_status='review_required',contact_evidence_url=url,contact_note='Exact normalized name and city; source and contact require manual verification')
        counts[item['contact_status']]+=1
        output.append(item)
    return output, dict(counts)

def main(argv=None):
    ap=argparse.ArgumentParser(description='ATLAS offline company contact review; no API calls or DB writes')
    ap.add_argument('--candidates',type=Path,default=Path('storage/app/prospect-imports/atlas-smart-preselection.csv'))
    ap.add_argument('--evidence',type=Path,default=Path('storage/app/prospect-imports/atlas-contact-evidence.csv'))
    ap.add_argument('--output',type=Path,default=Path('storage/app/prospect-imports/atlas-contact-review.csv'))
    args=ap.parse_args(argv)
    if not args.candidates.is_file(): ap.error('Candidate CSV not found')
    candidates=read_csv(args.candidates,BASE_FIELDS)
    if args.evidence.is_file():
        evidence=read_csv(args.evidence,EVIDENCE_FIELDS)
    else:
        evidence=[]
    merged,count=match(candidates,evidence)
    args.output.parent.mkdir(parents=True,exist_ok=True)
    with args.output.open('w',encoding='utf-8-sig',newline='') as f:
        w=csv.DictWriter(f,fieldnames=[*BASE_FIELDS,*OUTPUT_EXTRA]);w.writeheader();w.writerows(merged)
    print(json.dumps({'candidates':len(candidates),'evidence_rows':len(evidence),'result':count,'output':str(args.output),'db_writes':0,'network_calls':0},ensure_ascii=False,indent=2))
    print('NOTE: review_required DOES NOT mean verified; do not import blindly. No outreach performed.')
    return 0

if __name__ == '__main__':
    try: sys.exit(main())
    except (ValueError, OSError, csv.Error) as ex:
        print('ATLAS ERROR:',ex,file=sys.stderr);sys.exit(1)
