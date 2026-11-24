from pathlib import Path
import sys, shutil, datetime
root = Path.cwd()
layout = root/'resources/views/layouts/app.blade.php'
css = root/'resources/css/hnb-finish.css'
source_css = Path(__file__).resolve().parents[1]/'resources/css/hnb-finish.css'
if not layout.exists() or not (root/'artisan').exists() or not source_css.exists():
    raise SystemExit('ERROR: run from Laravel project root with unpacked patch files')
s = layout.read_text(encoding='utf-8-sig')
if '@vite(' not in s or '<body' not in s or 'resources/css/app.css' not in s:
    raise SystemExit('ERROR: unexpected layout; no changes applied')
new = s
if 'resources/css/hnb-finish.css' not in new:
    new = new.replace("'resources/css/app.css',", "'resources/css/app.css', 'resources/css/hnb-finish.css',",1)
if 'hnb-ui' not in new:
    new = new.replace('class="bg-white text-gray-900 antialiased font-sans"', 'class="hnb-ui bg-white text-gray-900 antialiased font-sans"',1)
if 'hnb-ui' not in new:
    raise SystemExit('ERROR: could not identify body class; no changes applied')
if 'hnb-topbar' not in new:
    fragment='class="sticky top-0 z-20 flex h-16 items-center gap-3'
    if fragment not in new: raise SystemExit('ERROR: could not identify topbar; no changes applied')
    new=new.replace(fragment,'class="hnb-topbar sticky top-0 z-20 flex h-16 items-center gap-3',1)
backup=root/('backup-ui-finish-'+datetime.datetime.now().strftime('%Y%m%d-%H%M%S-%f'))
backup.mkdir(exist_ok=False)
shutil.copy2(layout,backup/'app.blade.php')
if css.exists(): shutil.copy2(css,backup/'hnb-finish.css')
css.parent.mkdir(parents=True,exist_ok=True)
if source_css.resolve() != css.resolve():
    shutil.copy2(source_css,css)
layout.write_text(new,encoding='utf-8')
print('UI Sprint applied. Backup:',backup)
print('Vite entry: resources/css/hnb-finish.css')
