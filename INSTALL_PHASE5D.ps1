$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Ouvrez PowerShell dans le dossier Laravel (artisan introuvable).' }
$files = @(
 'app/Services/ProspectCsvImportService.php',
 'app/Console/Commands/ImportDiscoveredProspects.php',
 'tests/Unit/ProspectCsvImportServiceTest.php'
)
foreach ($file in $files) { if (-not (Test-Path (Join-Path 'PATCH' $file))) { throw "Fichier PATCH absent: $file" } }
$backup = "backup-phase5D-$(Get-Date -Format yyyyMMdd-HHmmss)"
foreach ($file in $files) {
 if (Test-Path $file) {
   $destination=Join-Path $backup $file
   New-Item -ItemType Directory -Path (Split-Path $destination) -Force | Out-Null
   Copy-Item $file $destination -Force
 }
 New-Item -ItemType Directory -Path (Split-Path $file) -Force | Out-Null
 Copy-Item (Join-Path 'PATCH' $file) $file -Force
}
New-Item -ItemType Directory -Path 'storage/app/prospect-imports' -Force | Out-Null
php artisan optimize:clear
if ($LASTEXITCODE -ne 0) { throw 'Echec optimize:clear' }
php artisan test --filter=ProspectCsvImportServiceTest
if ($LASTEXITCODE -ne 0) { throw 'Echec tests: voir les erreurs ci-dessus' }
php artisan list --raw | Select-String 'prospects:import-csv'
Write-Host "Phase 5D installee. Backup: $backup. Aucun prospect importe automatiquement." -ForegroundColor Green
