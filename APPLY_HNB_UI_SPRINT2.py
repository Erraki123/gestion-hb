from pathlib import Path
from datetime import datetime
import shutil, sys
root=Path.cwd()
layout=root/'resources/views/layouts/app.blade.php'
dash=root/'resources/views/admin/dashboard.blade.php'
pros=root/'resources/views/prospects/index.blade.php'
source=Path(__file__).resolve().parent/'hnb-premium-v2.css'
target=root/'public/css/hnb-premium-v2.css'
files=[layout,dash,pros]
if not (root/'artisan').is_file() or not all(p.is_file() for p in files):
    sys.exit('ERROR: expected Laravel project root and 3 Blade views. No changes applied.')
texts={p:p.read_text(encoding='utf-8-sig') for p in files}
if '<body' not in texts[layout] or '<x-app-layout' not in texts[dash] or 'prospect-compact' not in texts[pros]:
    sys.exit('ERROR: unexpected view structure. No changes applied.')
ld=texts[layout]
if 'hnb-ui' not in ld:
    sys.exit('ERROR: Install UI Sprint 1 first; hnb-ui wrapper missing. No changes applied.')
marker='<!-- HNB_UI_SPRINT2 -->'
if marker not in ld:
    ld=ld.replace('</head>', '    '+marker+'\n    <link rel="stylesheet" href="{{ asset(\'css/hnb-premium-v2.css\') }}">\n</head>',1)
    if marker not in ld:sys.exit('ERROR: </head> not found')
texts[layout]=ld
s=texts[dash]
s=s.replace('class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8"','class="hnb-dash-v2 py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8"',1)
s=s.replace('class="group rounded-xl border border-gray-200 p-4 transition hover:border-amber-400 hover:bg-amber-50"','class="hnb-priority-card group rounded-xl border border-gray-200 p-4 transition hover:border-amber-400 hover:bg-amber-50"')
s=s.replace('class="mb-3 text-sm font-semibold text-gray-700"','class="hnb-section-label mb-3 text-sm font-semibold text-gray-700"')
# Repair known double-decoded UTF-8 labels in admin dashboard only.
fix={
 'activitÃ©':'activité','rafraÃ®chissement':'rafraîchissement','HN BLOOM Â·':'HN BLOOM ·',
 'devis Â·':'devis ·','â†’':'→','Ã ':'à','dÃ©sinscriptions':'désinscriptions',
 'actualisÃ©s':'actualisés','EncaissÃ©':'Encaissé','ChÃ¨ques':'Chèques',
 'chÃ¨que(s)':'chèque(s)','Commissions Ã ':'Commissions à','dÃ©jÃ ':'déjà',
 'Ã€ rÃ©approvisionner':'À réapprovisionner','Ã€ surveiller':'À surveiller',
 'RÃ©sultats':'Résultats','dÃ©sactivÃ©':'désactivé','DÃ©tail':'Détail',
 'Reste Ã ':'Reste à','encaisser Ã ':'encaisser à',
}
for before,after in fix.items():s=s.replace(before,after)
texts[dash]=s
texts[pros]=texts[pros].replace('class="prospect-compact mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"','class="hnb-prospects-v2 prospect-compact mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"',1)
backup=root/('backup-hnb-ui-sprint2-'+datetime.now().strftime('%Y%m%d-%H%M%S'))
backup.mkdir(exist_ok=False)
for p in files:
    saved=backup/p.relative_to(root);saved.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,saved)
if target.exists():
    saved=backup/target.relative_to(root);saved.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(target,saved)
try:
    for p,value in texts.items():p.write_text(value,encoding='utf-8')
    target.parent.mkdir(parents=True,exist_ok=True)
    shutil.copy2(source,target)
except Exception:
    for p in files:shutil.copy2(backup/p.relative_to(root),p)
    if (backup/target.relative_to(root)).exists():shutil.copy2(backup/target.relative_to(root),target)
    elif target.exists():target.unlink()
    raise
print('HNB UI Sprint2 installed. Backup:',backup)
print('CSS loaded directly from /css/hnb-premium-v2.css; no npm build required.')
