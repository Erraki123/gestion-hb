$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Executez ce script dans Laravel (artisan introuvable).' }
if (-not (Test-Path '.\PATCH\app\Services\ProspectDiscoveryService.php')) { throw 'PATCH introuvable : decompressez le ZIP a la racine Laravel.' }
$files=@(
'config/prospect_discovery.php',
'database/migrations/2026_11_14_230000_create_discovered_prospects_table.php',
'app/Models/DiscoveredProspect.php',
'app/Services/ProspectDiscoveryService.php',
'app/Console/Commands/DiscoverProspects.php',
'app/Http/Controllers/Admin/DiscoveryReviewController.php',
'routes/discovery_phase5b.php',
'resources/views/prospects/_discovery_review.blade.php',
'tests/Unit/ProspectDiscoveryServiceTest.php'
)
$backup = "backup-phase5B-$(Get-Date -Format yyyyMMdd-HHmmss)"
foreach($file in $files){$source=Join-Path 'PATCH' $file;if(-not(Test-Path $source)){throw "Manquant: $source"}}
foreach($file in $files){if(Test-Path $file){$to=Join-Path $backup $file;New-Item -ItemType Directory -Force -Path (Split-Path $to) | Out-Null;Copy-Item $file $to -Force}}
foreach($file in @('routes/web.php','routes/console.php','resources/views/prospects/index.blade.php')){if(-not(Test-Path $file)){throw "Fichier projet absent: $file"};$to=Join-Path $backup $file;New-Item -ItemType Directory -Force -Path (Split-Path $to) | Out-Null;Copy-Item $file $to -Force}
foreach($file in $files){New-Item -ItemType Directory -Force -Path (Split-Path $file) | Out-Null;Copy-Item (Join-Path 'PATCH' $file) $file -Force}
$routeLine = "require __DIR__ . '/discovery_phase5b.php';"
$routes=Get-Content 'routes/web.php' -Raw
if(-not $routes.Contains($routeLine)){Add-Content -Path 'routes/web.php' -Value "`r`n$routeLine" -Encoding utf8}
$scheduleLine = "\Illuminate\Support\Facades\Schedule::command('prospects:discover')->dailyAt('09:15')->withoutOverlapping();"
$console=Get-Content 'routes/console.php' -Raw
if(-not $console.Contains("Schedule::command('prospects:discover')")){Add-Content -Path 'routes/console.php' -Value "`r`n$scheduleLine" -Encoding utf8}
$view='resources/views/prospects/index.blade.php'
$content=Get-Content $view -Raw
$include="@include('prospects._discovery_review')"
if(-not $content.Contains($include)){$content=$content.Replace('<x-app-layout dark-header>',"<x-app-layout dark-header>`r`n$include");Set-Content -Path $view -Value $content -Encoding utf8}
php artisan optimize:clear
if($LASTEXITCODE -ne 0){throw 'Echec optimize:clear'}
php artisan migrate --force
if($LASTEXITCODE -ne 0){throw 'Echec migrate'}
php artisan test --filter=ProspectDiscoveryServiceTest
if($LASTEXITCODE -ne 0){throw 'Echec test'}
php artisan view:cache
if($LASTEXITCODE -ne 0){throw 'Echec view:cache'}
php artisan schedule:list
Write-Host "Phase 5B installee. Backup: $backup. Collecte OFF par defaut." -ForegroundColor Green
