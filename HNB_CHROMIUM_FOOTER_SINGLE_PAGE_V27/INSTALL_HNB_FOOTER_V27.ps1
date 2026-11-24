$ErrorActionPreference = 'Stop'
$project = (Get-Location).Path
$target = Join-Path $project 'resources\views\pdf\chrome\style.blade.php'
if (-not (Test-Path $target)) { throw 'Cannot find Chromium PDF stylesheet. Run in Laravel project root.' }
$source = [System.IO.File]::ReadAllText($target)
if ($source -notmatch '\.page-footer\s*\{' -or $source -notmatch '</style>') {
    throw 'Unexpected Chromium CSS structure. No changes made.'
}
$marker = 'HNB_CHROME_FOOTER_PAGEFIX_V27'
if ($source.Contains($marker)) { Write-Host 'V27 already installed. No changes.' -ForegroundColor Yellow; exit 0 }
$backup = Join-Path $project ('backup-hnb-footer-v27-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
Copy-Item $target (Join-Path $backup 'style.blade.php') -Force
$rules = @'
/* HNB_CHROME_FOOTER_PAGEFIX_V27: fixes blank trailing page caused by bottom:-22mm */
@page { size: A4 portrait; margin: 13mm 13mm 20mm; }
@media print {
  html, body { height: auto !important; overflow: visible !important; }
  .sheet { max-width: none; padding-bottom: 16mm; }
  .page-footer {
    position: fixed !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    height: auto !important;
    min-height: 0 !important;
    max-height: 17mm;
    padding: 3px 0 2px !important;
    margin: 0 !important;
    break-before: auto !important;
    break-after: auto !important;
    page-break-before: auto !important;
    page-break-after: auto !important;
    background: #fff !important;
  }
  .footer-flex { align-items: flex-start; }
  .footer-block { overflow-wrap: anywhere; }
  .footer-block.address { max-width: 43%; }
}
'@
$updated = $source.Replace('</style>', "$rules`r`n</style>")
try {
  [System.IO.File]::WriteAllText($target, $updated, [System.Text.UTF8Encoding]::new($false))
  & php artisan view:clear
  if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
  & php artisan view:cache
  if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
  Write-Host 'SUCCESS: Footer pagination correction installed (V27).' -ForegroundColor Green
  Write-Host "Backup: $backup"
  Write-Host 'No controllers, routes, approval logic or DB changes.'
} catch {
  Copy-Item (Join-Path $backup 'style.blade.php') $target -Force
  throw "Install failed and backed-up CSS restored: $_"
}
