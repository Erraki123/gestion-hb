$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Ouvrez PowerShell dans la racine Laravel (artisan introuvable).' }
$payload = Join-Path $root 'HNB_PDF_PAGINATION_SAFE_V13\payload\resources\views\pdf'
$target = Join-Path $root 'resources\views\pdf'
$names = @('_header.blade.php','_style.blade.php','_footer.blade.php')
foreach ($name in $names) {
    if (-not (Test-Path (Join-Path $payload $name))) { throw "Fichier ZIP manquant: $name" }
    if (-not (Test-Path (Join-Path $target $name))) { throw "Fichier projet manquant: $name" }
}
$backup = Join-Path $root ('backup-pdf-v13-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
foreach ($name in $names) { Copy-Item (Join-Path $target $name) (Join-Path $backup $name) -Force }
try {
    foreach ($name in $names) { Copy-Item (Join-Path $payload $name) (Join-Path $target $name) -Force }
    foreach ($name in $names) {
        & php -l (Join-Path $target $name)
        if ($LASTEXITCODE -ne 0) { throw "Erreur de syntaxe: $name" }
    }
    & php artisan view:clear
    if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
    & php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
} catch {
    foreach ($name in $names) { Copy-Item (Join-Path $backup $name) (Join-Path $target $name) -Force }
    & php artisan view:clear
    throw "Installation interrompue. Restauré depuis $backup. $($_.Exception.Message)"
}
Write-Host "PDF V13 installé. Backup: $backup" -ForegroundColor Green
Write-Host 'Aucune donnée CRM, contrôleur ou permission modifiée.'
