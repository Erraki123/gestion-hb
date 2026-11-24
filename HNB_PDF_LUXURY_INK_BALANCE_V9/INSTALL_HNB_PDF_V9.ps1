$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Ouvrir PowerShell dans le dossier Laravel contenant artisan.' }
$source = Join-Path $root 'HNB_PDF_LUXURY_INK_BALANCE_V9\payload\resources\views\pdf'
$files = @('_header.blade.php', '_footer.blade.php', '_style.blade.php')
foreach ($file in $files) { if (-not (Test-Path (Join-Path $source $file))) { throw "ZIP incomplet : $file" } }
$destination = Join-Path $root 'resources\views\pdf'
$backup = Join-Path $root ('backup-pdf-lowink-v9-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
foreach ($file in $files) { Copy-Item (Join-Path $destination $file) (Join-Path $backup $file) -Force }
try {
  foreach ($file in $files) { Copy-Item (Join-Path $source $file) (Join-Path $destination $file) -Force }
  foreach ($file in $files) {
    & php -l (Join-Path $destination $file)
    if ($LASTEXITCODE -ne 0) { throw "PHP syntax error: $file" }
  }
  & php artisan view:clear
  if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
  & php artisan view:cache
  if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
} catch {
  foreach ($file in $files) { Copy-Item (Join-Path $backup $file) (Join-Path $destination $file) -Force }
  & php artisan view:clear | Out-Null
  throw ('Installation failed and files restored: ' + $_.Exception.Message)
}
Write-Host 'SUCCESS: HN BLOOM Premium Low-Ink V9 installed.' -ForegroundColor Green
Write-Host ('Backup: ' + $backup)
Write-Host 'Only shared PDF presentation files changed. No database, routes, controllers or approvals changed.'
