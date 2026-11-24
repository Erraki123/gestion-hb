$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Executez ce script a la racine Laravel (artisan requis).' }
$files = @(
 'app/Services/CrmConversationContextService.php',
 'app/Http/Controllers/Admin/AiFloatingController.php',
 'tests/Unit/CrmConversationContextTest.php'
)
foreach ($f in $files) { if (-not (Test-Path (Join-Path 'PATCH' $f))) { throw "Fichier du patch introuvable : $f" } }
$backup = 'backup-phase6A-' + (Get-Date -Format 'yyyyMMdd-HHmmss')
foreach ($f in $files) {
 $source=Join-Path 'PATCH' $f
 $dest=Join-Path (Get-Location) $f
 if (Test-Path $dest) {
  $save=Join-Path $backup $f
  New-Item -ItemType Directory -Force -Path (Split-Path $save) | Out-Null
  Copy-Item $dest $save -Force
 }
 New-Item -ItemType Directory -Force -Path (Split-Path $dest) | Out-Null
 Copy-Item $source $dest -Force
}
Write-Host "Backup: $backup" -ForegroundColor Yellow
php -l app/Services/CrmConversationContextService.php
if ($LASTEXITCODE -ne 0) { throw 'PHP syntax failure in service' }
php -l app/Http/Controllers/Admin/AiFloatingController.php
if ($LASTEXITCODE -ne 0) { throw 'PHP syntax failure in controller' }
php artisan optimize:clear
if ($LASTEXITCODE -ne 0) { throw 'Laravel optimize:clear failed' }
php artisan test --filter=CrmConversationContextTest
if ($LASTEXITCODE -ne 0) { throw 'Laravel unit tests failed' }
php artisan view:cache
if ($LASTEXITCODE -ne 0) { throw 'Blade compilation failed' }
Write-Host 'Phase 6A installee. Verifiez ensuite avec un Admin et un Commercial.' -ForegroundColor Green
