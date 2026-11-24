$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Run this script from your Laravel project folder.' }
$files = @('app/Services/AtlasOpportunityService.php','tests/Unit/AtlasSectorRecognitionTest.php')
$view = 'resources/views/prospects/_discovery_review.blade.php'
foreach ($p in $files) { if (-not (Test-Path (Join-Path 'PATCH' $p))) { throw "Missing patch: $p" } }
if (-not (Test-Path $view)) { throw 'Phase 6G Prospection view is missing.' }
$bak = 'backup-atlas-phase6h-v2-' + (Get-Date -Format 'yyyyMMdd-HHmmss')
foreach ($p in @($view) + $files) {
  if (Test-Path $p) {
    $target = Join-Path $bak $p
    New-Item -ItemType Directory -Force -Path (Split-Path $target) | Out-Null
    Copy-Item $p $target -Force
  }
}
try {
  foreach ($p in $files) {
    New-Item -ItemType Directory -Force -Path (Split-Path $p) | Out-Null
    Copy-Item (Join-Path 'PATCH' $p) $p -Force
  }
  php -l 'app/Services/AtlasOpportunityService.php'
  if ($LASTEXITCODE -ne 0) { throw 'PHP syntax failed.' }
  php '.\APPLY_ATLAS_6H.php'
  if ($LASTEXITCODE -ne 0) { throw 'View patch failed.' }
  php artisan test --filter=AtlasOpportunityServiceTest
  if ($LASTEXITCODE -ne 0) { throw 'Regression tests failed.' }
  php artisan test --filter=AtlasSectorRecognitionTest
  if ($LASTEXITCODE -ne 0) { throw 'New tests failed.' }
  php artisan view:cache
  if ($LASTEXITCODE -ne 0) { throw 'Blade compilation failed.' }
  Write-Host "ATLAS 6H V2 installed. Backup: $bak. No new companies, no database changes, no external requests." -ForegroundColor Green
} catch {
  Write-Host 'Installation failed; restoring earlier files...' -ForegroundColor Yellow
  foreach ($p in @($view) + $files) {
    $saved = Join-Path $bak $p
    if (Test-Path $saved) { Copy-Item $saved $p -Force }
    elseif (Test-Path $p) { Remove-Item $p -Force }
  }
  php artisan view:clear | Out-Null
  throw
}
