$ErrorActionPreference = 'Stop'
$project = (Get-Location).Path
if (-not (Test-Path (Join-Path $project 'artisan'))) { throw 'Execute this installer from Laravel project root.' }
$payload = Join-Path $PSScriptRoot 'payload'
$paths = @(
  'resources\views\pdf\_header.blade.php',
  'resources\views\pdf\_footer.blade.php',
  'resources\views\pdf\_style.blade.php',
  'resources\views\invoices\pdf.blade.php'
)
$originalInvoice = Join-Path $project 'resources\views\invoices\pdf.blade.php'
$invoiceText = [System.IO.File]::ReadAllText($originalInvoice)
if ($invoiceText -match 'HNB EXECUTIVE V17') { Write-Host 'V17 already installed.'; exit 0 }
# Ensure target invoice is still compatible with known document sections.
foreach ($required in @('DocumentTotals::breakdown', 'transport_amount', 'installation_amount', 'creditedAmount', '$invoice->payments', 'pdf._footer')) {
  if (-not $invoiceText.Contains($required)) { throw "Invoice view differs (missing $required). No changes made." }
}
foreach ($rel in $paths) { if (-not (Test-Path (Join-Path $project $rel))) { throw "Missing $rel. No changes made." } }
$backup = Join-Path $project ('backup-hnb-pdf-v17-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Force $backup | Out-Null
foreach ($rel in $paths) {
  $back = Join-Path $backup $rel
  New-Item -ItemType Directory -Force (Split-Path $back) | Out-Null
  Copy-Item (Join-Path $project $rel) $back -Force
}
$iconsDest = Join-Path $project 'public\images\hnb-pdf-icons'
$hadIcons = Test-Path $iconsDest
if ($hadIcons) { Copy-Item $iconsDest (Join-Path $backup 'hnb-pdf-icons') -Recurse -Force }
try {
  foreach ($rel in $paths) {
    $source = Join-Path $payload $rel
    if ($rel -like '*invoices*') {
      # Do not overwrite invoice view if it differs from the tested base file:
      # preserve any later application changes, requiring a separate review.
      $actualHash = (Get-FileHash (Join-Path $project $rel) -Algorithm SHA256).Hash
      if ($actualHash -ne '1A6D6DA7DF6B55C11073287FA154F52C3A2B0094AEF2439CB470527D3EF05671') { throw 'Invoice PDF view was customized. No changes made. Send current invoices/pdf.blade.php for tailored patch.' }
    }
    Copy-Item $source (Join-Path $project $rel) -Force
  }
  if (-not (Test-Path $iconsDest)) { New-Item -ItemType Directory -Force $iconsDest | Out-Null }
  Copy-Item (Join-Path $payload 'public\images\hnb-pdf-icons\*.png') $iconsDest -Force
  foreach ($rel in $paths) {
    & php -l (Join-Path $project $rel)
    if ($LASTEXITCODE -ne 0) { throw "PHP lint failed: $rel" }
  }
  & php artisan view:clear
  if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
  & php artisan view:cache
  if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
  Write-Host 'HNB EXECUTIVE V17 INSTALLED' -ForegroundColor Green
  Write-Host ('Backup: ' + $backup)
  Write-Host 'Only PDF Blade and PDF icon assets changed; no DB, routes or workflow changes.'
} catch {
  foreach ($rel in $paths) {
    Copy-Item (Join-Path $backup $rel) (Join-Path $project $rel) -Force
  }
  if ($hadIcons) { Remove-Item $iconsDest -Recurse -Force -ErrorAction SilentlyContinue; Copy-Item (Join-Path $backup 'hnb-pdf-icons') $iconsDest -Recurse -Force }
  else { Remove-Item $iconsDest -Recurse -Force -ErrorAction SilentlyContinue }
  & php artisan view:clear | Out-Null
  throw "RESTORED ORIGINAL FILES: $($_.Exception.Message)"
}
