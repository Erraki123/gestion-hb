$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Ouvrez PowerShell dans le dossier Laravel (artisan).' }
$zip = Join-Path $env:USERPROFILE 'Downloads\HN_BLOOM_Phase4D_Fix_Admin_Commercial.zip'
if (-not (Test-Path $zip)) { throw "ZIP introuvable: $zip" }
if (-not (Test-Path '.\routes\ai_floating_phase4d.php')) { throw 'Installez Phase 4D avant ce correctif.' }
if (-not (Test-Path '.\resources\views\layouts\_ai_floating.blade.php')) { throw 'La vue Phase 4D est introuvable.' }
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = "backup-phase4D-roles-$stamp"
New-Item -ItemType Directory -Force -Path "$backup\routes" | Out-Null
New-Item -ItemType Directory -Force -Path "$backup\resources\views\layouts" | Out-Null
Copy-Item '.\routes\ai_floating_phase4d.php' "$backup\routes\ai_floating_phase4d.php"
Copy-Item '.\resources\views\layouts\_ai_floating.blade.php' "$backup\resources\views\layouts\_ai_floating.blade.php"
Expand-Archive $zip -DestinationPath . -Force
php artisan optimize:clear
if ($LASTEXITCODE -ne 0) { throw 'optimize:clear failed' }
php artisan route:list --path=admin/ai-floating -v
if ($LASTEXITCODE -ne 0) { throw 'route:list failed' }
php artisan view:cache
if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
Write-Host "Correctif IA Admin + Commercial installe. Sauvegarde: $backup" -ForegroundColor Green
