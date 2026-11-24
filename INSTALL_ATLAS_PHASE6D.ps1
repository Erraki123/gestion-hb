$ErrorActionPreference='Stop'
if (-not (Test-Path './artisan')) { throw 'Launch from Laravel project root.' }
$files=@('app/Services/AtlasOpportunityService.php','app/Console/Commands/AtlasRadar.php','resources/views/prospects/_atlas_radar.blade.php','tests/Unit/AtlasOpportunityServiceTest.php')
foreach($f in $files) { if(-not (Test-Path (Join-Path 'PATCH' $f))) { throw "Patch file missing: $f" } }
$target='resources/views/prospects/_discovery_review.blade.php'
if (-not (Test-Path $target)) { throw 'Phase 6B missing: discovery review not found.' }
$backup='backup-atlas-phase6d-'+(Get-Date -Format 'yyyyMMdd-HHmmss')
foreach($f in @($target)+$files) { if(Test-Path $f) { $dest=Join-Path $backup $f; New-Item -ItemType Directory -Force (Split-Path $dest) | Out-Null; Copy-Item $f $dest -Force } }
foreach($f in $files) { New-Item -ItemType Directory -Force (Split-Path $f) | Out-Null; Copy-Item (Join-Path 'PATCH' $f) $f -Force }
$insert="@include('prospects._atlas_radar')"
$contents=[IO.File]::ReadAllText((Resolve-Path $target).Path)
if (-not $contents.Contains($insert)) {
  $needle='  <div class="space-y-4 p-4">'
  if (-not $contents.Contains($needle)) { throw 'UI marker not found; automatic modification stopped.' }
  $contents=$contents.Replace($needle, $needle+"`r`n   "+$insert)
  [IO.File]::WriteAllText((Resolve-Path $target).Path,$contents,(New-Object Text.UTF8Encoding($false)))
}
php -l 'app/Services/AtlasOpportunityService.php'; if($LASTEXITCODE -ne 0){throw 'PHP service syntax error'}
php -l 'app/Console/Commands/AtlasRadar.php'; if($LASTEXITCODE -ne 0){throw 'PHP command syntax error'}
php artisan test --filter=AtlasOpportunityServiceTest; if($LASTEXITCODE -ne 0){throw 'Atlas tests failed'}
php artisan view:cache; if($LASTEXITCODE -ne 0){throw 'Blade error'}
php artisan list --raw | Select-String 'atlas:radar'
Write-Host "ATLAS Phase 6D installed. Backup: $backup. No scraping initiated and no messages sent." -ForegroundColor Green
