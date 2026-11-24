from pathlib import Path
from datetime import datetime
import shutil, sys

root=Path.cwd()
file=root/'app'/'Http'/'Controllers'/'QuoteController.php'
if not (root/'artisan').is_file() or not file.is_file():
    sys.exit('ERROR: Run from Laravel project root (artisan and QuoteController.php required).')
raw=file.read_bytes()
try:
    content=raw.decode('utf-8-sig')
except UnicodeDecodeError:
    sys.exit('ERROR: QuoteController.php is not UTF-8. No changes made.')
if 'function formData(' in content:
    print('SKIP: formData() already present. No modification made.')
    sys.exit(0)
needle='    public function create(Request $request)'
if content.count(needle)!=1:
    sys.exit('ERROR: Could not safely identify QuoteController::create. No changes made.')
if 'use App\\Models\\Setting;' not in content:
    sys.exit('ERROR: Setting import missing; cannot apply expected fix safely.')
insert=(
    '    /** Data required by quotes._form for create and edit. */\n'
    '    private function formData(): array\n'
    '    {\n'
    '        return [\n'
    "            'mode' => Setting::priceMode(),\n"
    "            'rates' => Setting::taxRates(),\n"
    "            'defaultRate' => (float) config('commerce.tax_rate', 20),\n"
    '        ];\n'
    '    }\n\n'
)
backup=root/('backup-quote-formdata-'+datetime.now().strftime('%Y%m%d-%H%M%S'))
backup.mkdir(exist_ok=False)
shutil.copy2(file,backup/file.name)
updated=content.replace(needle,insert+needle,1)
# Preserve BOM if present
file.write_bytes((b'\xef\xbb\xbf' if raw.startswith(b'\xef\xbb\xbf') else b'') + updated.encode('utf-8'))
print('PATCH APPLIED. Backup:',backup)
print('Changed:',file)
