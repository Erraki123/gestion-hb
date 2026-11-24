# HN BLOOM V28 - approval distinction + conditional discount
from pathlib import Path
from datetime import datetime
import shutil, subprocess, re, sys

root=Path.cwd()
controller=root/'app/Http/Controllers/Admin/HnbChromePdfController.php'
view=root/'resources/views/pdf/chrome/document.blade.php'
if not (root/'artisan').is_file() or not controller.is_file() or not view.is_file():
    sys.exit('STOP: Run from Laravel project root. Expected files not found.')

sources={f:f.read_text(encoding='utf-8-sig') for f in (controller,view)}
php=sources[controller]
blade=sources[view]

# Only PDF Studio download is affected. Sender/release gate remains untouched.
needle=r"'quote'\s*=>\s*\$document->approval_status\s*===\s*'approved'\s*&&\s*\$release->canRelease\(\$document\),"
repl="'quote' => $document->approval_status === 'approved', // V28: PDF after quote approval; client sending still requires release approval"
if 'V28: PDF after quote approval' not in php:
    php,n=re.subn(needle, lambda _:repl,php,count=1)
    if n!=1:sys.exit('STOP: Quote approval code differs from audited version. No changes.')

# Other document approvals and ownership remain exactly as they were.
if not all(s in php for s in ["abort_unless((int) $document->commercial_id", "'invoice' =>", "'delivery' =>", "'receipt' =>"]):
    sys.exit('STOP: Security checks differ from audited version. No changes.')

# Text displaying REMISE in table header and row is conditional on a non-zero discount.
# Compute after doc array is built, before line-table markup.
compute="""  @php
    // V28: Show Remise only if at least one line actually has a non-zero discount.
    $hnbShowRemise = in_array($doc['kind'], ['quote', 'invoice'], true)
        && collect($doc['items'] ?? [])->contains(function ($line) {
            $value = preg_replace('/[^0-9,.-]/', '', (string) ($line['discount'] ?? '0'));
            $value = str_replace(',', '.', (string) $value);
            return abs((float) $value) > 0.00001;
        });
  @endphp
"""
if 'V28: Show Remise' not in blade:
    pivot='  @if(count($doc[\'items\']))'
    if blade.count(pivot)!=1:sys.exit('STOP: Cannot find unique items section. No changes.')
    blade=blade.replace(pivot,compute+'\n'+pivot,1)

if '@if($hnbShowRemise)<th style="width:10%">REMISE</th>@endif' in blade:
    pass
elif '<th style="width:10%">REMISE</th>' in blade:
    blade=blade.replace('<th style="width:10%">REMISE</th>', '@if($hnbShowRemise)<th style="width:10%">REMISE</th>@endif',1)
else:
    sys.exit('STOP: Discount header was changed; no changes.')

if '@if($hnbShowRemise)<td class="num">{{ $item[\'discount\'] }} %</td>@endif' in blade:
    pass
elif '<td class="num">{{ $item[\'discount\'] }} %</td>' in blade:
    blade=blade.replace('<td class="num">{{ $item[\'discount\'] }} %</td>','@if($hnbShowRemise)<td class="num">{{ $item[\'discount\'] }} %</td>@endif',1)
else:
    sys.exit('STOP: Discount cells were changed; no changes.')

if php==sources[controller] and blade==sources[view]:
    print('V28 already installed. No changes.')
    sys.exit(0)

stamp=datetime.now().strftime('%Y%m%d-%H%M%S')
backup=root/('backup-hnb-chrome-v28-'+stamp)
backup.mkdir(parents=True)
for file in (controller,view):
    copy=backup/file.relative_to(root)
    copy.parent.mkdir(parents=True,exist_ok=True)
    shutil.copy2(file,copy)

try:
    controller.write_text(php,encoding='utf-8')
    view.write_text(blade,encoding='utf-8')
    commands=[['php','-l',str(controller)],['php','artisan','view:clear'],['php','artisan','view:cache']]
    for cmd in commands:
        print('CHECK:', ' '.join(cmd),flush=True)
        ret=subprocess.run(cmd,cwd=root)
        if ret.returncode!=0: raise RuntimeError('Validation failed: '+' '.join(cmd))
except Exception as e:
    for file in (controller,view):
        shutil.copy2(backup/file.relative_to(root),file)
    subprocess.run(['php','artisan','view:clear'],cwd=root)
    print('RESTORED BACKUP:',backup)
    sys.exit(str(e))
print('SUCCESS: V28 installed')
print('PDF Studio: commercial quote approved => download allowed; send-to-client protection unchanged')
print('Remise column only shown when at least one item discount is non-zero')
print('Backup:',backup)
