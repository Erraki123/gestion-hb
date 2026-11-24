$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Ouvrez PowerShell dans la racine du projet Laravel (artisan introuvable).' }
$source = Join-Path $PSScriptRoot 'payload'
$targetView = Join-Path $root 'resources\views\pdf'
$targetIcons = Join-Path $root 'public\images\hnb-pdf-icons'
$backup = Join-Path $root ('backup-pdf-v15-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Force -Path $backup | Out-Null
$files = @('_header.blade.php','_footer.blade.php','_style.blade.php')
foreach ($f in $files) {
    if (-not (Test-Path (Join-Path $targetView $f))) { throw "Missing existing file: $f" }
    Copy-Item (Join-Path $targetView $f) (Join-Path $backup $f) -Force
}
if (Test-Path $targetIcons) {
    Copy-Item $targetIcons (Join-Path $backup 'hnb-pdf-icons') -Recurse -Force
}
try {
    foreach ($f in $files) { Copy-Item (Join-Path $source "resources\views\pdf\$f") (Join-Path $targetView $f) -Force }
    New-Item -ItemType Directory -Force -Path $targetIcons | Out-Null
    Copy-Item (Join-Path $source 'public\images\hnb-pdf-icons\*.png') $targetIcons -Force
    foreach ($f in $files) {
        & php -l (Join-Path $targetView $f)
        if ($LASTEXITCODE -ne 0) { throw "PHP syntax invalid: $f" }
    }
    & php artisan view:clear
    if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
    & php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
    Write-Host 'HNB PDF LUXURY ICONS V15 INSTALLED' -ForegroundColor Green
    Write-Host "BACKUP: $backup"
    Write-Host 'PDF views/assets only. No database changes, no workflow changes.'
} catch {
    foreach ($f in $files) { Copy-Item (Join-Path $backup $f) (Join-Path $targetView $f) -Force }
    if (Test-Path (Join-Path $backup 'hnb-pdf-icons')) {
        Remove-Item $targetIcons -Recurse -Force -ErrorAction SilentlyContinue
        Copy-Item (Join-Path $backup 'hnb-pdf-icons') $targetIcons -Recurse -Force
    }
    & php artisan view:clear | Out-Null
    throw "Installation failed, PDF views restored from backup: $($_.Exception.Message)"
}
