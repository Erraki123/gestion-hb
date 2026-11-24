from pathlib import Path
import re, shutil, subprocess, sys
from datetime import datetime

root=Path.cwd()
files={
 'quote':root/'app/Http/Controllers/QuoteController.php',
 'invoice':root/'app/Http/Controllers/InvoiceController.php',
}
studio=root/'app/Http/Controllers/Admin/HnbChromePdfController.php'
route=root/'routes/hnb_chrome_pdf.php'
if not all(p.is_file() for p in [*files.values(),studio,route]):
 sys.exit('ERROR: Missing Laravel controllers or Chromium PDF Studio route. No changes made.')
if 'admin.pdf-studio.show' not in route.read_text(encoding='utf-8-sig'):
 sys.exit('ERROR: Chrome Studio route not found. No changes made.')
if "'role:admin,commercial'" not in route.read_text(encoding='utf-8-sig'):
 sys.exit('ERROR: Studio is not enabled for both roles. No changes made.')
if 'authorizeStudio' not in studio.read_text(encoding='utf-8-sig') or 'canRelease(' not in studio.read_text(encoding='utf-8-sig'):
 sys.exit('ERROR: Cannot establish approval safety checks in Studio. No changes made.')

# Match only the known old DomPDF generation statements inside the pdf() method.
old={
 'quote': "        $pdf = Pdf::loadView('quotes.pdf', compact('quote'))->setPaper('a4');\n        return $request->boolean('download') ? $pdf->download($name) : $pdf->stream($name);",
 'invoice': "        $pdf  = Pdf::loadView('invoices.pdf', compact('invoice'))->setPaper('a4');\n\n        return $request->boolean('download') ? $pdf->download($name) : $pdf->stream($name);",
}
replacement={
 'quote': "        // HNB_CHROME_LEGACY_BRIDGE: keep the existing route; centralize PDF authorization in Studio.\n        return redirect()->route('admin.pdf-studio.show', [\n            'type' => 'quote', 'id' => $quote->id, 'download' => 1,\n        ]);",
 'invoice': "        // HNB_CHROME_LEGACY_BRIDGE: keep the existing route; centralize PDF authorization in Studio.\n        return redirect()->route('admin.pdf-studio.show', [\n            'type' => 'invoice', 'id' => $invoice->id, 'download' => 1,\n        ]);",
}
updated={}
for kind,p in files.items():
 raw=p.read_text(encoding='utf-8-sig')
 normalized=raw.replace('\r\n','\n')
 # Make sure replacement occurs inside correct method area.
 m=re.search(r'public\s+function\s+pdf\s*\(\s*Request\s+\$request\s*,\s*(Quote|Invoice)\s+\$(quote|invoice)\s*\)',normalized)
 if not m:
  sys.exit(f'ERROR: Cannot find pdf() method in {p.name}; no files modified.')
 fragment=normalized[m.end():]
 if old[kind] not in fragment[:2500]:
  if 'HNB_CHROME_LEGACY_BRIDGE' in fragment[:2500]:
   print(f'ALREADY INSTALLED: {p.name}')
   continue
  sys.exit(f'ERROR: {p.name} PDF method changed; refusing unsafe patch.')
 # Restrict to immediate method region.
 before=normalized[:m.end()]; after=normalized[m.end():]
 after=after.replace(old[kind],replacement[kind],1)
 updated[p]=before+after
if not updated:
 print('Chrome legacy bridge already installed on both controllers.')
 sys.exit(0)
backup=root/('backup-chrome-route-bridge-'+datetime.now().strftime('%Y%m%d-%H%M%S'))
backup.mkdir()
for p in updated:
 shutil.copy2(p,backup/p.name)
try:
 for p,s in updated.items():
  p.write_text(s,encoding='utf-8',newline='\n')
 for p in updated:
  r=subprocess.run(['php','-l',str(p)],check=False)
  if r.returncode: raise RuntimeError('PHP syntax error: '+p.name)
 for args in [['php','artisan','route:clear'],['php','artisan','view:clear']]:
  r=subprocess.run(args,check=False)
  if r.returncode: raise RuntimeError('Failed: '+' '.join(args))
except Exception as ex:
 for p in updated:
  shutil.copy2(backup/p.name,p)
 print('ERROR:',ex,'; original controllers restored')
 sys.exit(1)
print('SUCCESS: Existing quote/invoice PDF buttons now redirect to authorized Chromium PDF Studio.')
print('BACKUP:',backup)
print('No database, release sender, delivery, payment, stock or approval logic changed.')
