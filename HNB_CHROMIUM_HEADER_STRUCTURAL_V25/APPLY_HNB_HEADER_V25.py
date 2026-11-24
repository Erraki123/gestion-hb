from pathlib import Path
from datetime import datetime
import re, shutil, subprocess, sys
root=Path.cwd(); package=Path(__file__).resolve().parent
blade=root/'resources/views/pdf/chrome/document.blade.php'
style=root/'resources/views/pdf/chrome/style.blade.php'
if not (root/'artisan').is_file() or not blade.is_file() or not style.is_file():
    sys.exit('STOP: launch from Laravel project root; no changes made.')
old_b=blade.read_text(encoding='utf-8-sig')
old_s=style.read_text(encoding='utf-8-sig')
pattern=r'<header class="masthead(?: [^\"]*)?">.*?</header>'
if len(re.findall(pattern,old_b,flags=re.S))!=1:
    sys.exit('STOP: Chromium header not recognized, nothing changed.')
if old_s.count('</style>')!=1:
    sys.exit('STOP: CSS tag structure unknown, nothing changed.')
replacement=(package/'HEADER_V25.blade.php').read_text(encoding='utf-8')
new_b=re.sub(pattern,lambda m:replacement,old_b,count=1,flags=re.S)
# Remove legacy header CSS added by V23/V24 to avoid invisible override stacking.
new_s=re.sub(r'/\* HNB_CHROME_REFERENCE_HEADER_V23_START \*/.*?/\* HNB_CHROME_REFERENCE_HEADER_V23_END \*/','',old_s,flags=re.S)
new_s=re.sub(r'/\* HNB_HEADER_V24_START \*/.*?/\* HNB_HEADER_V24_END \*/','',new_s,flags=re.S)
new_s=re.sub(r'/\* HNB_HEADER_V25_START \*/.*?/\* HNB_HEADER_V25_END \*/','',new_s,flags=re.S)
new_s=new_s.replace('</style>','/* HNB_HEADER_V25_START */\n'+(package/'HEADER_V25.css').read_text(encoding='utf-8')+'\n/* HNB_HEADER_V25_END */\n</style>',1)
if 'hnb25-brandname' not in new_b or 'HNB_HEADER_V25_START' not in new_s:
    sys.exit('STOP: validation failed, no changes made.')
backup=root/('backup-hnb-chrome-header-v25-'+datetime.now().strftime('%Y%m%d-%H%M%S-%f'))
backup.mkdir()
for p in (blade,style): shutil.copy2(p,backup/p.name)
try:
    blade.write_text(new_b,encoding='utf-8')
    style.write_text(new_s,encoding='utf-8')
    for cmd in (["php","-l",str(blade)], ["php","-l",str(style)], ["php","artisan","view:clear"], ["php","artisan","view:cache"]):
        if subprocess.run(cmd,check=False).returncode!=0:
            raise RuntimeError('Failed: '+' '.join(cmd))
except Exception as exc:
    for p in (blade,style):shutil.copy2(backup/p.name,p)
    subprocess.run(['php','artisan','view:clear'],check=False)
    sys.exit('ROLLED BACK: '+str(exc))
print('SUCCESS: V25 header structure replaced (Blade + CSS).')
print('BACKUP:', backup)
print('No controller, database, invoice totals, or approval workflows changed.')
