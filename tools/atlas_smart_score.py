#!/usr/bin/env python3
"""ATLAS offline, explainable opportunity scoring. No DB/API/outreach.
Scores are routing priorities, NEVER likelihoods or verified customer needs.
"""
import argparse, csv, json, re, unicodedata
from pathlib import Path


def norm(s):
    t=unicodedata.normalize('NFKD', str(s or ''))
    t=''.join(c for c in t if not unicodedata.combining(c)).lower()
    return re.sub(r'\s+', ' ', re.sub(r'[^a-z0-9]+', ' ', t)).strip()

SECTORS = [
 ('hotellerie', ('hotel','riad','auberge','resort','hostel','guesthouse','maison d hotes'), 'Serrures intelligentes / Controle d acces', 76),
 ('sante', ('clinique','polyclinique','hopital','hospital','centre medical','cabinet medical'), 'Controle d acces / Securite electronique', 80),
 ('partenaire_securite', ('securite','security','domotique','videosurveillance','controle acces','installateur alarme'), 'Partenariat installateur / Revendeur', 73),
 ('industrie', ('usine','industrie','factory','entrepot','logistique','warehouse'), 'Controle d acces / Videosurveillance', 73),
 ('enseignement', ('ecole','school','universite','college','campus','institut scolaire'), 'Controle d acces / Videosurveillance', 68),
 ('commerce', ('marjane','supermarche','supermarket','hypermarch','centre commercial','mall'), 'Videosurveillance / Controle d acces', 61),
 ('banque', ('banque','bank','attijari','bmce','saham bank'), 'Controle d acces', 54),
]

def sector_of(name):
    n=norm(name)
    # Boundary-aware to avoid 'laboratoire conception et systemes' becoming medical.
    for sector, tokens, solution, base in SECTORS:
        for token in tokens:
            if re.search(r'(?<![a-z0-9])'+re.escape(token)+r'(?![a-z0-9])',n):
                return sector,solution,base,'Mot du nom: '+token
    return 'non_determine','Aucune solution specifique sans verification',20,'Activite non identifiable a partir du nom'

def calculate(row, contact=None):
    company=row.get('company',''); city=row.get('city','')
    sector,solution,score,reason=sector_of(company)
    # A name is an indicator, NOT a proof.
    source=(row.get('source_url') or '').strip()
    source_valid=source.startswith(('https://www.openstreetmap.org/','https://openstreetmap.org/'))
    contact=contact or {}
    status=contact.get('contact_status','missing')
    # Never count review_required as verified.
    proposed=sum(bool((contact.get(c) or '').strip()) for c in ('phone','email','website'))
    evidence=(contact.get('contact_evidence_url') or '').strip()
    has_source=bool(source_valid or (source.startswith('https://') and len(source)>10))
    conf=min(100, 8 + (25 if has_source else 0) + (10 if city else 0) + (12 if sector!='non_determine' else 0) + (10 if proposed else 0))
    if status=='review_required': conf=min(conf,65)
    if status in ('rejected','ambiguous'): conf=min(conf,30)
    # Penalize branch ambiguity; never collapse outlets into one buyer.
    try: branches=int(row.get('points_meme_nom_ville') or '1')
    except ValueError: branches=1
    if branches>1: score=max(0,score-8); conf=max(0,conf-12)
    if sector=='non_determine': score=min(score,30)
    next_step='Verifier le secteur et identifier le decideur'
    if proposed and status=='review_required': next_step='Controler les coordonnees proposees et la source'
    elif status in ('rejected','ambiguous'): next_step='Verifier manuellement la correspondance entreprise'
    return {**row, 'sector_v2':sector, 'opportunity_priority':score,
            'data_confidence':conf, 'solution_hypothesis':solution,
            'reason':reason+'; besoin non confirme', 'contact_status':status,
            'contact_fields_proposed':proposed, 'evidence_url':evidence,
            'next_step':next_step, 'risk':('Plusieurs points OSM de meme nom/ville; branches distinctes possibles' if branches>1 else ''),
            'ai_mode':'offline_rules_not_ai'}

def load(path):
    with path.open('r',encoding='utf-8-sig',newline='') as f:
        return list(csv.DictReader(f))

def run(source, contacts, output, report):
    if not source.is_file(): raise FileNotFoundError('Source manquante: '+str(source))
    candidates=load(source)
    found=load(contacts) if contacts.is_file() else []
    contact_map={(norm(r.get('company')),norm(r.get('city'))):r for r in found}
    scored=[calculate(r,contact_map.get((norm(r.get('company')),norm(r.get('city'))))) for r in candidates]
    scored.sort(key=lambda r:(-r['opportunity_priority'],-r['data_confidence'],norm(r['city']),norm(r['company'])))
    output.parent.mkdir(parents=True,exist_ok=True)
    fields=list(dict.fromkeys([k for r in scored for k in r.keys()]))
    with output.open('w',encoding='utf-8-sig',newline='') as f:
        writer=csv.DictWriter(f,fieldnames=fields);writer.writeheader();writer.writerows(scored)
    stats={'source_records':len(candidates),'contacts_review_required':sum(r['contact_status']=='review_required' for r in scored),
           'sectors':{s:sum(r['sector_v2']==s for r in scored) for s in sorted(set(r['sector_v2'] for r in scored))},
           'top_10':[{'company':r['company'],'city':r['city'],'priority':r['opportunity_priority'],'confidence':r['data_confidence'],'sector':r['sector_v2']} for r in scored[:10]],
           'note':'Scores are rules-based local priorities and evidence-completeness estimates; NOT purchase probability or verified customer demand.',
           'db_writes':0,'network_calls':0,'outbound_messages':0}
    report.write_text(json.dumps(stats,ensure_ascii=False,indent=2),encoding='utf-8')
    return stats

def main():
    a=argparse.ArgumentParser()
    base=Path('storage/app/prospect-imports')
    a.add_argument('--source',type=Path,default=base/'atlas-smart-review.csv')
    a.add_argument('--contacts',type=Path,default=base/'atlas-contact-review.csv')
    a.add_argument('--output',type=Path,default=base/'atlas-smart-score-v2.csv')
    a.add_argument('--report',type=Path,default=base/'atlas-smart-score-v2.json')
    args=a.parse_args()
    try: print(json.dumps(run(args.source,args.contacts,args.output,args.report),ensure_ascii=False,indent=2))
    except (OSError, ValueError) as err: a.exit(1,str(err)+'\n')
if __name__=='__main__':main()
