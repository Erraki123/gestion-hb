$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) {
    throw 'Ouvre PowerShell dans le dossier Laravel contenant artisan.'
}

$bundle = Join-Path $PSScriptRoot 'payload'
$targets = @(
    'resources\views\pdf\_style.blade.php',
    'resources\views\pdf\_header.blade.php',
    'resources\views\pdf\_footer.blade.php',
    'public\images\hnb-pdf-hybrid\header-ribbon.png',
    'public\images\hnb-pdf-hybrid\bottom-wave.png'
)
$backup = Join-Path $root ('backup-hnb-hybrid-v19-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null

$existing = @{}
foreach ($relative in $targets) {
    $source = Join-Path $bundle $relative
    $dest = Join-Path $root $relative
    if (-not (Test-Path $source)) { throw "Patch source missing: $relative" }
    $exists = Test-Path $dest
    $existing[$relative] = $exists
    if ($exists) {
        $copyPath = Join-Path $backup $relative
        New-Item -ItemType Directory -Path (Split-Path $copyPath) -Force | Out-Null
        Copy-Item -LiteralPath $dest -Destination $copyPath -Force
    }
}

try {
    foreach ($relative in $targets) {
        $source = Join-Path $bundle $relative
        $dest = Join-Path $root $relative
        New-Item -ItemType Directory -Path (Split-Path $dest) -Force | Out-Null
        Copy-Item -LiteralPath $source -Destination $dest -Force
    }

    foreach ($relative in @(
        'resources\views\pdf\_style.blade.php',
        'resources\views\pdf\_header.blade.php',
        'resources\views\pdf\_footer.blade.php'
    )) {
        php -l (Join-Path $root $relative)
        if ($LASTEXITCODE -ne 0) { throw "PHP syntax error: $relative" }
    }

    php artisan view:clear
    if ($LASTEXITCODE -ne 0) { throw 'Laravel view:clear failed' }
    php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'Laravel view:cache failed' }

    Write-Host 'SUCCESS: HN BLOOM Hybrid Luxury V19 installed.' -ForegroundColor Green
    Write-Host "BACKUP: $backup"
    Write-Host 'Only PDF design partials + two bounded PNG assets were updated.'
    Write-Host 'No controllers, database, approvals, or messaging changed.'
} catch {
    foreach ($relative in $targets) {
        $dest = Join-Path $root $relative
        if ($existing[$relative]) {
            $copyPath = Join-Path $backup $relative
            if (Test-Path $copyPath) { Copy-Item -LiteralPath $copyPath -Destination $dest -Force }
        } else {
            if (Test-Path $dest) { Remove-Item -LiteralPath $dest -Force }
        }
    }
    php artisan view:clear | Out-Null
    throw "Installation failed, original files restored: $_"
}
