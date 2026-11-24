from pathlib import Path
from datetime import datetime
import subprocess, sys, shutil, re

base = Path.cwd()
source = Path(__file__).resolve().parent
style = base / 'resources/views/pdf/chrome/style.blade.php'
blade = base / 'resources/views/pdf/chrome/document.blade.php'
if not (base / 'artisan').is_file() or not style.is_file() or not blade.is_file():
    sys.exit('ERROR: Run from Laravel project root with Chromium PDF Studio installed. Nothing changed.')
old = style.read_text(encoding='utf-8-sig')
page = blade.read_text(encoding='utf-8-sig')
if 'hnb-ref-masthead' not in page or 'hnb-ref-banner' not in page:
    sys.exit('ERROR: V23 header not found in document.blade.php. Nothing changed.')
if '</style>' not in old:
    sys.exit('ERROR: CSS style closing tag missing. Nothing changed.')
if 'HNB_HEADER_V24_START' in old:
    print('V24 is already applied. No changes.')
    sys.exit(0)
css = (source / 'HEADER_BALANCE_V24.css').read_text(encoding='utf-8')
new = old.replace('</style>', css + '\n</style>', 1)
backup = base / ('backup-chromium-header-v24-' + datetime.now().strftime('%Y%m%d-%H%M%S'))
backup.mkdir()
shutil.copy2(style, backup / style.name)
try:
    style.write_text(new, encoding='utf-8')
    for cmd in [['php', '-l', str(style)], ['php', 'artisan', 'view:clear'], ['php', 'artisan', 'view:cache']]:
        print('CHECK:', ' '.join(cmd),flush=True)
        if subprocess.run(cmd, check=False).returncode != 0:
            raise RuntimeError('Failed: ' + ' '.join(cmd))
except Exception as error:
    shutil.copy2(backup / style.name, style)
    subprocess.run(['php', 'artisan', 'view:clear'], check=False)
    sys.exit('ERROR: ' + str(error) + '. Original CSS restored.')
print('SUCCESS: compact, balanced V24 header installed.')
print('BACKUP:', backup)
print('Only Chromium PDF CSS changed; invoice data, permissions and workflows untouched.')
