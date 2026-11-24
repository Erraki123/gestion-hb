$ErrorActionPreference='Stop'
if(-not(Test-Path '.\artisan')){ throw 'Ouvrez PowerShell dans le dossier Laravel.' }
$files=@(
'config/prospect_qualification.php',
'database/migrations/2026_11_14_233000_add_qualification_to_discovered_prospects.php',
'app/Services/ProspectQualificationService.php',
'app/Console/Commands/QualifyDiscoveredProspects.php',
'resources/views/prospects/_discovery_review.blade.php',
'tests/Unit/ProspectQualificationServiceTest.php'
)
foreach($file in $files){if(-not(Test-Path (Join-Path 'PATCH' $file))){throw "PATCH absent: $file"}}
$backup="backup-phase5C-$(Get-Date -Format yyyyMMdd-HHmmss)"
foreach($file in ($files + @('routes/console.php'))){if(Test-Path $file){$target=Join-Path $backup $file;New-Item -ItemType Directory -Force -Path (Split-Path $target)|Out-Null;Copy-Item $file $target -Force}}
foreach($file in $files){New-Item -ItemType Directory -Force -Path (Split-Path $file)|Out-Null;Copy-Item (Join-Path 'PATCH' $file) $file -Force}
# Schedule once, keep any existing commands intact.
$line="\Illuminate\Support\Facades\Schedule::command('prospects:qualify')->dailyAt('09:35')->withoutOverlapping();"
$content=Get-Content '.\routes\console.php' -Raw
if(-not $content.Contains("Schedule::command('prospects:qualify')")){Add-Content '.\routes\console.php' -Value "`r`n$line" -Encoding utf8}
php artisan optimize:clear
if($LASTEXITCODE -ne 0){throw 'optimize:clear failed'}
php artisan migrate --force
if($LASTEXITCODE -ne 0){throw 'migrate failed'}
php artisan test --filter=ProspectQualificationServiceTest
if($LASTEXITCODE -ne 0){throw 'tests failed'}
php artisan view:cache
if($LASTEXITCODE -ne 0){throw 'view:cache failed'}
php artisan prospects:qualify --dry-run
Write-Host "Phase 5C installed. Backup: $backup" -ForegroundColor Green
