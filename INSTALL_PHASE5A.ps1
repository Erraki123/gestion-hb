$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Executez ce script depuis la racine Laravel (fichier artisan).' }
if (-not (Test-Path '.\PATCH\app\Services\CrmAgentPlanner.php')) { throw 'Dossier PATCH introuvable. Decompressez le ZIP dans Laravel.' }
$backup = "backup-phase5A-$(Get-Date -Format yyyyMMdd-HHmmss)"
$files = @(
  'app/Services/CrmAgentPlanner.php',
  'app/Services/CrmMultiAgentService.php',
  'app/Http/Controllers/Admin/AiFloatingController.php',
  'tests/Unit/CrmAgentPlannerTest.php'
)
foreach ($file in $files) {
  $source = Join-Path 'PATCH' $file
  if (-not (Test-Path $source)) { throw "Fichier PATCH manquant : $source" }
}
foreach ($file in $files) {
  $previous = Join-Path '.' $file
  if (Test-Path $previous) {
    $save = Join-Path $backup $file
    New-Item -ItemType Directory -Force -Path (Split-Path $save) | Out-Null
    Copy-Item $previous $save -Force
  }
}
foreach ($file in $files) {
  $destination = Join-Path '.' $file
  New-Item -ItemType Directory -Force -Path (Split-Path $destination) | Out-Null
  Copy-Item (Join-Path 'PATCH' $file) $destination -Force
}
php artisan optimize:clear
if ($LASTEXITCODE -ne 0) { throw 'Echec optimize:clear' }
php artisan test --filter=CrmAgentPlannerTest
if ($LASTEXITCODE -ne 0) { throw 'Echec des tests CrmAgentPlannerTest. Ne pas deployer.' }
php artisan view:cache
if ($LASTEXITCODE -ne 0) { throw 'Echec view:cache' }
Write-Host "Phase 5A installee. Backup: $backup" -ForegroundColor Green
