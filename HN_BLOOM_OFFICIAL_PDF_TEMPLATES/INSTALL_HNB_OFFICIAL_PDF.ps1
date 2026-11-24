param()
$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Run from the Laravel project root (artisan missing).' }
$pack = Split-Path -Parent $MyInvocation.MyCommand.Path
$bladeSource = Join-Path $pack 'laravel\resources\views\pdf\hnbloom-official.blade.php'
$logoSource = Join-Path $pack 'laravel\public\images\hnbloom_logo_reference.png'
$bladeDest = Join-Path $root 'resources\views\pdf\hnbloom-official.blade.php'
$logoDest = Join-Path $root 'public\images\hnbloom_logo_reference.png'
$backup = Join-Path $root ('backup-hnb-official-pdf-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Force $backup | Out-Null
if (Test-Path $bladeDest) { Copy-Item $bladeDest (Join-Path $backup 'hnbloom-official.blade.php') -Force }
if (Test-Path $logoDest) { Copy-Item $logoDest (Join-Path $backup 'hnbloom_logo_reference.png') -Force }
New-Item -ItemType Directory -Force (Split-Path $bladeDest -Parent) | Out-Null
New-Item -ItemType Directory -Force (Split-Path $logoDest -Parent) | Out-Null
Copy-Item $bladeSource $bladeDest -Force
Copy-Item $logoSource $logoDest -Force
php artisan view:clear
if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
php artisan view:cache
if ($LASTEXITCODE -ne 0) { throw 'view:cache failed; restore from backup' }
Write-Host 'HN BLOOM template installed. Existing document PDF routes remain unchanged.' -ForegroundColor Green
Write-Host ('Backup: ' + $backup)
