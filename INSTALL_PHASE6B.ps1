$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Ouvrez PowerShell dans le dossier Laravel (artisan introuvable).' }
$files = @(
  'app/Services/ProspectReviewFilterService.php',
  'app/Http/Controllers/Admin/DiscoveryCsvUploadController.php',
  'routes/discovery_phase6b.php',
  'resources/views/prospects/_discovery_review.blade.php',
  'tests/Unit/ProspectReviewFilterServiceTest.php'
)
foreach($file in $files) { if (-not (Test-Path (Join-Path 'PATCH' $file))) { throw "Fichier manquant: $file" } }
foreach($file in @('routes/web.php','app/Services/ProspectCsvImportService.php','app/Models/DiscoveredProspect.php')) {
 if (-not (Test-Path $file)) { throw "Phase 5B/5D requise: $file" }
}
$backup = "backup-phase6B-$(Get-Date -Format yyyyMMdd-HHmmss)"
foreach($file in ($files + @('routes/web.php'))) {
 if (Test-Path $file) {
  $to = Join-Path $backup $file
  New-Item -ItemType Directory -Force -Path (Split-Path $to) | Out-Null
  Copy-Item $file $to -Force
 }
}
foreach($file in $files) {
 New-Item -ItemType Directory -Force -Path (Split-Path $file) | Out-Null
 Copy-Item (Join-Path 'PATCH' $file) $file -Force
}
$route = "require __DIR__ . '/discovery_phase6b.php';"
$web = Get-Content '.\routes\web.php' -Raw
if (-not $web.Contains($route)) { Add-Content '.\routes\web.php' -Value "`r`n$route" -Encoding UTF8 }
php artisan optimize:clear
if ($LASTEXITCODE -ne 0) { throw 'Echec optimize:clear' }
php artisan test --filter=ProspectReviewFilterServiceTest
if ($LASTEXITCODE -ne 0) { throw 'Echec test Phase6B' }
php artisan view:cache
if ($LASTEXITCODE -ne 0) { throw 'Echec view:cache' }
php artisan route:list --name=admin.discovery.upload-csv
if ($LASTEXITCODE -ne 0) { throw 'Route CSV absente' }
Write-Host "Phase 6B installee. Backup: $backup. Aucun prospect ajoute automatiquement." -ForegroundColor Green
