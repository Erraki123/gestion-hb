$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Run from Laravel project root (artisan missing).' }
$source = Join-Path $PSScriptRoot 'payload'
$dest = Join-Path $root 'resources\views\pdf'
$icons = Join-Path $root 'public\images\hnb-pdf-icons'
$files = @('_header.blade.php', '_footer.blade.php', '_style.blade.php')
$backup = Join-Path $root ('backup-hnb-pdf-v16-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Force -Path $backup | Out-Null
foreach ($f in $files) {
    if (-not (Test-Path (Join-Path $dest $f))) { throw "Missing source file: $f" }
    Copy-Item (Join-Path $dest $f) (Join-Path $backup $f) -Force
}
$iconsExisted = Test-Path $icons
if ($iconsExisted) { Copy-Item $icons (Join-Path $backup 'hnb-pdf-icons') -Recurse -Force }
try {
    foreach ($f in $files) { Copy-Item (Join-Path $source "resources\views\pdf\$f") (Join-Path $dest $f) -Force }
    New-Item -ItemType Directory -Force -Path $icons | Out-Null
    Copy-Item (Join-Path $source 'public\images\hnb-pdf-icons\*.png') $icons -Force
    foreach ($f in $files) {
        & php -l (Join-Path $dest $f)
        if ($LASTEXITCODE -ne 0) { throw "PHP syntax invalid: $f" }
    }
    & php artisan view:clear
    if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
    & php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
    Write-Host 'HNB PDF V16 EDITORIAL INSTALLED' -ForegroundColor Green
    Write-Host "Backup: $backup"
    Write-Host 'Only PDF styling assets changed, no controllers or database.'
} catch {
    foreach ($f in $files) { Copy-Item (Join-Path $backup $f) (Join-Path $dest $f) -Force }
    if ($iconsExisted) {
        Remove-Item $icons -Recurse -Force -ErrorAction SilentlyContinue
        Copy-Item (Join-Path $backup 'hnb-pdf-icons') $icons -Recurse -Force
    } else {
        Remove-Item $icons -Recurse -Force -ErrorAction SilentlyContinue
    }
    & php artisan view:clear | Out-Null
    throw "Installation failed, restored PDF files: $($_.Exception.Message)"
}
