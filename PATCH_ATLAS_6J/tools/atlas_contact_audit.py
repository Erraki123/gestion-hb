#!/usr/bin/env python3
"""ATLAS 6J: local contact coverage audit and optional ephemeral Places preview.

No DB write, no Google content storage, no emails/messages. Requires explicit flag
for paid API calls. Search responses printed for interactive verification only.
"""
import argparse
import csv
import json
import os
from pathlib import Path
import re
import sys
import unicodedata
from collections import Counter
from urllib.request import Request, urlopen
from urllib.error import HTTPError, URLError

CSV_FIELDS = ('company', 'city', 'category', 'phone', 'email', 'website', 'source_url', 'id')
PLACES_ENDPOINT = 'https://places.googleapis.com/v1/places:searchText'


def normalize(value):
    value = unicodedata.normalize('NFKD', str(value or '').casefold())
    value = ''.join(c for c in value if not unicodedata.combining(c))
    return re.sub(r'[^a-z0-9]+', ' ', value).strip()


def reasonable_match(name, city, candidate_name, address):
    """Conservative heuristic; never presents a match as verified."""
    a, b = normalize(name), normalize(candidate_name)
    if not a or not b or not normalize(city):
        return False
    a_words, b_words = set(a.split()), set(b.split())
    if not a_words or not b_words:
        return False
    overlap = len(a_words & b_words) / max(1, len(a_words))
    city_ok = normalize(city) in normalize(address)
    return city_ok and overlap >= 0.7


def load_rows(path):
    with path.open('r', encoding='utf-8-sig', newline='') as f:
        reader = csv.DictReader(f)
        if not reader.fieldnames or not {'company', 'city'}.issubset(reader.fieldnames):
            raise ValueError('CSV requires company and city columns')
        return [row for row in reader if (row.get('company') or '').strip()]


def audit(rows):
    missing = Counter()
    categories = Counter()
    for r in rows:
        for field in ('phone', 'email', 'website'):
            if not (r.get(field) or '').strip():
                missing[field] += 1
        categories[(r.get('category') or 'unknown').strip()] += 1
    return {'records': len(rows), 'missing_contact': dict(missing), 'by_category': dict(categories),
            'note': 'Source OSM does not guarantee professional contacts; no information is fabricated.'}


def places_search(name, city, key, timeout=12):
    body = json.dumps({'textQuery': f'{name}, {city}, Morocco', 'regionCode': 'MA', 'pageSize': 3}).encode('utf-8')
    request = Request(PLACES_ENDPOINT, data=body, method='POST', headers={
        'Content-Type': 'application/json',
        'X-Goog-Api-Key': key,
        'X-Goog-FieldMask': 'places.id,places.displayName,places.formattedAddress,places.nationalPhoneNumber,places.websiteUri',
    })
    with urlopen(request, timeout=timeout) as response:
        payload = json.loads(response.read(150000).decode('utf-8'))
    return payload.get('places', []) if isinstance(payload, dict) else []


def preview(rows, key, limit):
    # No persistence, export or cache of Google Places Content. Google attribution needed in future UI.
    for idx, row in enumerate(rows[:limit], 1):
        name, city = (row.get('company') or '').strip(), (row.get('city') or '').strip()
        print(f'[{idx}/{min(limit,len(rows))}] {name} — {city}', flush=True)
        if not name or not city:
            print('  Skip: missing identity')
            continue
        try:
            matches = places_search(name, city, key)
        except (HTTPError, URLError, TimeoutError, ValueError, json.JSONDecodeError) as exc:
            code = getattr(exc, 'code', '')
            print(f'  Lookup failed (HTTP {code} or network error). Check key, quotas, connectivity.')
            continue
        if not matches:
            print('  No results')
            continue
        found = False
        for place in matches:
            label = place.get('displayName', {}).get('text', '')
            address = place.get('formattedAddress', '')
            if not reasonable_match(name, city, label, address):
                continue
            found = True
            print('  Possible match — VERIFY manually; Google Places source, not an independent confirmation')
            print('  Name:', label)
            print('  Address:', address)
            print('  Phone:', place.get('nationalPhoneNumber') or 'Not supplied')
            print('  Website:', place.get('websiteUri') or 'Not supplied')
            print('  Place ID:', place.get('id') or 'Not supplied')
            print('  Email: not supplied by Places API')
            break
        if not found:
            print('  No sufficiently similar match; nothing asserted')
    print('Google Places data shown temporarily only; not stored in CRM or CSV.')


def main(argv=None):
    p = argparse.ArgumentParser(description='ATLAS local contact coverage and optional Google Places preview')
    p.add_argument('--csv', type=Path, default=Path('storage/app/prospect-imports/atlas-smart-preselection.csv'))
    p.add_argument('--provider', choices=['none', 'google'], default='none')
    p.add_argument('--limit', type=int, default=3)
    p.add_argument('--confirm-paid-requests', action='store_true')
    a = p.parse_args(argv)
    if not a.csv.is_file():
        p.error(f'CSV not found: {a.csv}')
    if not 1 <= a.limit <= 5:
        p.error('--limit must be between 1 and 5')
    rows = load_rows(a.csv)
    print(json.dumps(audit(rows), ensure_ascii=False, indent=2))
    if a.provider == 'none':
        print('Offline audit only: 0 external requests, 0 CRM writes.')
        return 0
    if not a.confirm_paid_requests:
        p.error('Google Places requests may incur charges. Add --confirm-paid-requests to opt in.')
    key = os.environ.get('ATLAS_GOOGLE_PLACES_KEY', '').strip()
    if not key:
        p.error('ATLAS_GOOGLE_PLACES_KEY is not set; API connection remains disabled.')
    preview(rows, key, a.limit)
    return 0


if __name__ == '__main__':
    try:
        sys.exit(main())
    except (ValueError, OSError, csv.Error) as exc:
        print(f'ATLAS ERROR: {exc}', file=sys.stderr)
        sys.exit(1)
