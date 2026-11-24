from pathlib import Path
import re, shutil, sys, datetime, subprocess
base=Path.cwd()
source=Path(__file__).resolve().parent
blade=base/'resources/views/pdf/chrome/document.blade.php'
style=base/'resources/views/pdf/chrome/style.blade.php'
if not (base/'artisan').exists() or not blade.exists() or not style.exists():
    sys.exit('ERREUR: Execute depuis la racine du projet Laravel avec Chromium PDF Studio installe.')
original={p:p.read_text(encoding='utf-8-sig') for p in (blade,style)}
pat=r'<header class="masthead(?: [^"]*)?">.*?</header>'
if len(re.findall(pat,original[blade],flags=re.S)) != 1:
    sys.exit('ERREUR: Header Chromium non reconnu. Rien modifie.')
if '</style>' not in original[style]:
    sys.exit('ERREUR: style.blade.php invalide. Rien modifie.')
header=(source/'HEADER_REPLACEMENT.blade.php.txt').read_text(encoding='utf-8')
css=(source/'HEADER_STYLE_V23.css').read_text(encoding='utf-8')
new_blade=re.sub(pat,lambda _:header,original[blade],count=1,flags=re.S)
new_style=re.sub(r'/\* HNB_CHROME_REFERENCE_HEADER_V23_START \*/.*?/\* HNB_CHROME_REFERENCE_HEADER_V23_END \*/','',original[style],flags=re.S)
new_style=new_style.replace('</style>',css+'\n</style>',1)
backup=base/('backup-chromium-header-v23-'+datetime.datetime.now().strftime('%Y%m%d-%H%M%S-%f'))
backup.mkdir()
for p in (blade,style): shutil.copy2(p,backup/p.name)
try:
    blade.write_text(new_blade,encoding='utf-8')
    style.write_text(new_style,encoding='utf-8')
    for cmd in [['php','-l',str(blade)],['php','-l',str(style)],['php','artisan','view:clear'],['php','artisan','view:cache']]:
        print('CHECK:', ' '.join(cmd),flush=True)
        if subprocess.run(cmd,check=False).returncode != 0:raise RuntimeError('Check failed')
except Exception as e:
    for p in (blade,style):shutil.copy2(backup/p.name,p)
    subprocess.run(['php','artisan','view:clear'],check=False)
    sys.exit('INSTALL FAILED, originals restored: '+str(e))
print('SUCCESS: Chromium Header V23 installed (other PDF content unchanged).')
print('BACKUP:',backup)
print('No changes to database, controller, permissions, legacy DomPDF or payment data.')
