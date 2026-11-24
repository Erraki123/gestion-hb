#!/usr/bin/env python3
"""Offline, explainable selection of OSM candidates for HN BLOOM.
Does not contact any service, access the CRM database, or invent contact details.
"""
import argparse
import csv
import json
import re
import unicodedata
from collections import Counter, defaultdict
from pathlib import Path

SECTORS = [
    ('sante', 86, ['clinique', 'clinic', 'hospital', 'hopital', 'polyclinique', 'medical', 'centre medical', 'laboratoire'], 'Controle d acces, videosurveillance'),
    ('hotellerie', 82, ['hotel', 'hostel', 'riad', 'resort', 'auberge', 'guesthouse', 'maison d hotes'], 'Controle d acces, serrures intelligentes'),
    ('industrie', 79, ['usine', 'factory', 'industrie', 'industrial', 'entrepot', 'logistique', 'warehouse'], 'Controle d acces, videosurveillance'),
    ('enseignement', 71, ['ecole', 'school', 'universite', 'university', 'college', 'campus', 'institut'], 'Controle d acces, videosurveillance'),
    ('commerce', 63, ['supermarche', 'supermarket', 'hypermarche', 'marjane', 'mall', 'centre commercial', 'market'], 'Videosurveillance, controle d acces'),
    ('banque', 50, ['banque', 'bank', 'attijari', 'saham', 'credit', 'bmce'], 'Controle d acces (besoin non verifie)'),
    ('securite_partenaire', 76, ['securite', 'security', 'domotique', 'alarme', 'camera', 'surveillance', 'controle d acces'], 'Partenariat / verification du domaine'),
]
HEADERS = ['company','city','category','phone','email','website','source_url','id']


def norm(value):
    v = unicodedata.normalize('NFKD', value or '')
    v = ''.join(c for c in v if not unicodedata.combining(c))
    v = v.lower().replace('&',' et ')
    return re.sub(r'\s+', ' ', re.sub('[^a-z0-9]+', ' ', v)).strip()


def classify(row):
    name = norm(row.get('company',''))
    # Businesses named 'security' are potential partners, not automatically end customers.
    partner = SECTORS[-1]
    if any(term in name for term in partner[2]):
        return partner
    for sector in SECTORS[:-1]:
        if any(term in name for term in sector[2]):
            return sector
    return ('non_determine', 15, [], 'Aucun besoin identifiable depuis le nom seul')


def run(src, review, safe, stats, limit):
    if not src.is_file():
        raise FileNotFoundError(str(src))
    with src.open('r', encoding='utf-8-sig', newline='') as f:
        reader = csv.DictReader(f)
        if not set(HEADERS).issubset(set(reader.fieldnames or [])):
            raise ValueError('Colonnes CSV manquantes: ' + ', '.join(HEADERS))
        items = list(reader)
    seen_osm = set()
    groups = defaultdict(list)
    invalid = 0
    for row in items:
        company, city = row['company'].strip(), row['city'].strip()
        if not company or not city:
            invalid += 1
            continue
        oid = row['id'].strip()
        if oid and oid in seen_osm:
            continue
        if oid: seen_osm.add(oid)
        # Collapses same-named branches into one review card *per city*, preserving counts.
        groups[(norm(city), norm(company))].append(row)

    reviewed = []
    for (_, _), branches in groups.items():
        row = branches[0]
        sector, base, _, products = classify(row)
        # This is a heuristic *priority*, not a probability or AI confidence.
        contacts = sum(bool(row.get(k, '').strip()) for k in ('phone', 'email', 'website'))
        priority = min(100, base + contacts * 4)
        if sector == 'non_determine':
            priority = min(priority, 25)
        reviewed.append({
            **{key: row.get(key, '') for key in HEADERS},
            'sector_estime': sector,
            'priorite_heuristique': priority,
            'points_meme_nom_ville': len(branches),
            'action': 'Verifier la fiche OSM et le siege avant contact' if len(branches)>1 else 'Verifier activite et contact avant prospection',
            'piste_produit': products,
            'preuve': 'Nom + localisation OSM uniquement; besoin non verifie',
            'identifiants_osm': '|'.join(b.get('id','') for b in branches),
        })
    reviewed.sort(key=lambda r: (-r['priorite_heuristique'], -r['points_meme_nom_ville'], r['city'].casefold(), r['company'].casefold()))
    review.parent.mkdir(parents=True, exist_ok=True)
    fields = HEADERS + ['sector_estime','priorite_heuristique','points_meme_nom_ville','action','piste_produit','preuve','identifiants_osm']
    with review.open('w', encoding='utf-8-sig', newline='') as f:
        writer=csv.DictWriter(f,fieldnames=fields); writer.writeheader(); writer.writerows(reviewed)
    selected = [r for r in reviewed if r['priorite_heuristique'] >= 60][:limit]
    with safe.open('w', encoding='utf-8-sig', newline='') as f:
        writer=csv.DictWriter(f,fieldnames=HEADERS); writer.writeheader()
        for r in selected:
            writer.writerow({k: r[k] for k in HEADERS})
    report = {
        'lignes_source':len(items),'lignes_invalides':invalid,
        'fiches_uniques_nom_ville':len(reviewed), 'entites_osm_uniques':len(seen_osm),
        'fiches_multisites':sum(len(b)>1 for b in groups.values()),
        'fiches_priorite_60_plus':sum(r['priorite_heuristique']>=60 for r in reviewed),
        'preselection_limitee':len(selected),
        'par_secteur':dict(Counter(r['sector_estime'] for r in reviewed)),
        'warning':'Priorite heuristique explicable, pas de modele IA ni verification des besoins; echantillon OSM limite a 40 POI par ville au stade extraction.'
    }
    stats.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding='utf-8')
    return report


def main():
    parser=argparse.ArgumentParser()
    parser.add_argument('--source',required=True,type=Path)
    parser.add_argument('--output-dir',required=True,type=Path)
    parser.add_argument('--limit',type=int,default=60)
    args=parser.parse_args()
    if not 1<=args.limit<=300: parser.error('--limit doit etre 1..300')
    d=args.output_dir; d.mkdir(parents=True,exist_ok=True)
    report=run(args.source,d/'atlas-smart-review.csv',d/'atlas-smart-preselection.csv',d/'atlas-smart-report.json',args.limit)
    print(json.dumps(report,ensure_ascii=False,indent=2))
    print('REVIEW:', d/'atlas-smart-review.csv')
    print('PRESELECTION:', d/'atlas-smart-preselection.csv')
    print('Aucune ecriture dans la base CRM; aucun contact envoye.')

if __name__=='__main__':
    main()
