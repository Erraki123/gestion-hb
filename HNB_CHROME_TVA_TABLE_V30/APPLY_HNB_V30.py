from pathlib import Path
from datetime import datetime
import shutil, subprocess, sys, re

root=Path.cwd()
f=root/'resources/views/pdf/chrome/document.blade.php'
if not (root/'artisan').is_file() or not f.is_file():
    sys.exit('STOP: execute from Laravel project root; Blade missing.')
old=f.read_text(encoding='utf-8-sig')
if 'HNB_V30_TVA_SUMMARY_ONLY' in old:
    print('V30 already installed. No changes.')
    sys.exit(0)

# Reject if the user's current V28 conditional Remise logic is absent: avoid inadvertently changing it.
if 'hnbShowRemise' not in old:
    sys.exit('STOP: V28 conditional Remise not detected. Please install V28 first. No changes.')

# Match exactly the tax column in the product table, avoiding other tax lines and summaries.
vat_header=re.compile(r'(?m)^([ \t]*)<th\s+style="width:9%">TVA</th>[ \t]*\r?\n')
vat_cell=re.compile(r'(?m)^([ \t]*)<td\s+class="num">\{\{\s*\$item\[\'tax_rate\'\]\s*\}\}\s*%\s*</td>[ \t]*\r?\n')
if len(vat_header.findall(old))!=1 or len(vat_cell.findall(old))!=1:
    sys.exit('STOP: TVA product column structure differs from expected template. No changes.')

new=vat_header.sub('',old,count=1)
new=vat_cell.sub('',new,count=1)
# Adjust flexible table widths without touching Remise conditional layout or delivery table.
new=new.replace('<th style="width:12%">RÉF.</th>','<th style="width:14%">RÉF.</th>',1)
new=new.replace('<th style="width:13%">QTÉ</th>','<th style="width:14%">QTÉ</th>',1)
new=new.replace('<th style="width:13%">PRIX HT</th>','<th style="width:16%">PRIX HT</th>',1)
new=new.replace('<th style="width:15%">TOTAL HT</th>','<th style="width:19%">TOTAL HT</th>',1)
new=new.replace('  @if(count($doc[\'items\']))','  {{-- HNB_V30_TVA_SUMMARY_ONLY: TVA excluded from product table; preserved in totals. --}}\n  @if(count($doc[\'items\']))',1)
if new==old or "$item['tax_rate']" in new or '<th style="width:9%">TVA</th>' in new:
    sys.exit('STOP: patch validation failed; no changes.')

backup=root/('backup-hnb-v30-tva-'+datetime.now().strftime('%Y%m%d-%H%M%S'))
bf=backup/f.relative_to(root)
bf.parent.mkdir(parents=True,exist_ok=True)
shutil.copy2(f,bf)
try:
    f.write_text(new,encoding='utf-8')
    for cmd in (['php','-l',str(f)],['php','artisan','view:clear'],['php','artisan','view:cache']):
        print('CHECK:', ' '.join(cmd),flush=True)
        result=subprocess.run(cmd,cwd=root)
        if result.returncode!=0: raise RuntimeError('Failed: '+' '.join(cmd))
except Exception as e:
    shutil.copy2(bf,f)
    subprocess.run(['php','artisan','view:clear'],cwd=root)
    sys.exit(f'FAILED; restored backup {backup}: {e}')
print('SUCCESS: TVA removed from product table only. Totals and calculations unchanged.')
print('Conditional Remise from V28 preserved.')
print('Backup:',backup)
