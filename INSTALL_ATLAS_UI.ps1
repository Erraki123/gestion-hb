$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Run this script in the Laravel project root.' }
$files = @('resources/views/prospects/_atlas_radar.blade.php', 'resources/views/prospects/_discovery_review.blade.php')
foreach ($file in $files) {
  if (-not (Test-Path (Join-Path 'PATCH' $file))) { throw "Missing patch: $file" }
  if (-not (Test-Path $file)) { throw "Existing view not found: $file. Install Phase 6B/6D first." }
}
$backup = 'backup-atlas-ui-' + (Get-Date -Format 'yyyyMMdd-HHmmss')
foreach ($file in $files) {
  $destination = Join-Path $backup $file
  New-Item -ItemType Directory -Path (Split-Path $destination) -Force | Out-Null
  Copy-Item $file $destination -Force
  Copy-Item (Join-Path 'PATCH' $file) $file -Force
}
php artisan view:clear
if ($LASTEXITCODE -ne 0) { throw 'View clear failed' }
php artisan view:cache
if ($LASTEXITCODE -ne 0) {
  Write-Host "Blade compilation failed; restoring $backup" -ForegroundColor Red
  foreach ($file in $files) { Copy-Item (Join-Path $backup $file) $file -Force }
  php artisan view:clear
  throw 'View compilation failed; original files restored.'
}
Write-Host "ATLAS UI installed. Backup: $backup" -ForegroundColor Green
Write-Host 'No database changes, no imports, no messages.' -ForegroundColor Green
