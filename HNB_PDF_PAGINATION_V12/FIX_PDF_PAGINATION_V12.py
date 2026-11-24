from pathlib import Path
from datetime import datetime
import subprocess, shutil, re, sys

root = Path.cwd()
if not (root / 'artisan').is_file():
    sys.exit('ERROR: Ouvrir PowerShell dans le dossier Laravel contenant artisan.')
base = root / 'resources/views/pdf'
header = base / '_header.blade.php'
style = base / '_style.blade.php'
if not header.is_file() or not style.is_file():
    sys.exit('ERROR: _header.blade.php / _style.blade.php introuvable.')
raw_h = header.read_text(encoding='utf-8-sig')
raw_s = style.read_text(encoding='utf-8-sig')
if 'HNB_PDF_PAGINATION_V12' in raw_s:
    print('V12 deja installe. Aucune modification.'); sys.exit(0)
if raw_s.count('</style>') != 1:
    sys.exit('ERROR: Structure CSS inhabituelle. Rien modifie.')

# Remove only the optional full A4 decorative element and its Blade condition.
new_h = raw_h
removed=[]
patterns = [
    ('A4 gold frame', r'''(?s)\s*\{\{--[^\n]*filigree[^\n]*--\}\}\s*@if\s*\(file_exists\(public_path\(['\"]images/hnb-v7-zakhrafa-frame\.png['\"]\)\)\)\s*<div\s+class=["']hnb-v7-page-ornament["'][^>]*>.*?</div>\s*@endif'''),
    ('A4 gold frame (no comment)', r'''(?s)\s*@if\s*\(file_exists\(public_path\(['\"]images/hnb-v7-zakhrafa-frame\.png['\"]\)\)\)\s*<div\s+class=["']hnb-v7-page-ornament["'][^>]*>.*?</div>\s*@endif'''),
    ('A4 frame only', r'''(?s)\s*<div\s+class=["']hnb-v7-page-ornament["'][^>]*>.*?</div>'''),
    ('watermark wrapper', r'''(?s)\s*@if\s*\(\$hasWm\)\s*<div\s+class=["']watermark["'][^>]*>.*?</div>\s*@endif'''),
]
for label, pat in patterns:
    new_h, n = re.subn(pat, '\n', new_h)
    if n: removed.append(f'{label}: {n}')

# Additional old decorative standalone elements, without changing document content.
for cls in ['hnb-v6-bottom-deco','hnb-v6-bottom-seal','hnb-v6-top-medallion','hnb-gold-art-top','hnb-gold-art-bottom']:
    new_h, n = re.subn(r'(?s)\s*<div\s+class=["\']'+cls+r'["\'][^>]*>.*?</div>', '\n', new_h)
    if n: removed.append(f'{cls}: {n}')

fix = r'''
/* HNB_PDF_PAGINATION_V12 */
@page { size: A4 portrait; margin: 0 !important; }
html, body { margin:0 !important; padding:0 !important; height:auto !important; min-height:0 !important; }
.watermark, .hnb-v7-page-ornament, .hnb-v7-page-ornament img,
.hnb-v6-bottom-deco, .hnb-v6-bottom-seal, .hnb-v6-top-medallion,
.hnb-gold-art-top, .hnb-gold-art-bottom { display:none !important; }
/* Decorative border drawn with CSS only. No full-page image or flow height. */
.head-shell { position:relative; }
.head-shell:before { content:""; position:absolute; top:4px; left:4px; width:23px; height:23px; border-left:1px solid #D4A332; border-top:1px solid #D4A332; }
.head-shell:after { content:""; position:absolute; top:4px; right:4px; width:23px; height:23px; border-right:1px solid #D4A332; border-top:1px solid #D4A332; }
.footer { background:#fffdfa !important; }
'''
new_s = raw_s.replace('</style>',fix+'\n</style>',1)
backup = root / ('backup-pdf-pagination-v12-'+datetime.now().strftime('%Y%m%d-%H%M%S'))
backup.mkdir(exist_ok=False)
for p in [header,style]:
    shutil.copy2(p,backup/p.name)

try:
    header.write_text(new_h, encoding='utf-8')
    style.write_text(new_s, encoding='utf-8')
    for cmd in [['php','-l',str(header)],['php','-l',str(style)],['php','artisan','view:clear'],['php','artisan','view:cache']]:
        result=subprocess.run(cmd,check=False)
        if result.returncode != 0:
            raise RuntimeError('Echec: '+ ' '.join(cmd))
except Exception as exc:
    for p in [header,style]: shutil.copy2(backup/p.name,p)
    subprocess.run(['php','artisan','view:clear'],check=False)
    sys.exit(f'ERROR: {exc}. Fichiers restaures. Backup: {backup}')
print('\nSUCCESS: V12 installed. A4 ornament HTML removed when present.')
print('Removed:', ', '.join(removed) if removed else 'No recognized decorative HTML found; CSS safety rules applied')
print('Backup:', backup)
print('No database, no controllers, no approvals or outbound messages changed.')
print('Create a NEW PDF. If extra blank pages remain, send actual _header/_footer/_style files and page count.')
