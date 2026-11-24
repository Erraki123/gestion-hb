$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Execute from Laravel root (artisan missing).' }
$src = Join-Path $PSScriptRoot 'payload\resources\views\pdf'
$dst = Join-Path $root 'resources\views\pdf'
$names = @('_header.blade.php', '_style.blade.php', '_footer.blade.php')
foreach ($name in $names) {
    if (-not (Test-Path (Join-Path $src $name))) { throw "Patch missing: $name" }
    if (-not (Test-Path (Join-Path $dst $name))) { throw "Project file missing: $name" }
}
$backup = Join-Path $root ('backup-hnb-pdf-v14-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
foreach ($name in $names) { Copy-Item (Join-Path $dst $name) (Join-Path $backup $name) -Force }
$installed = $false
try {
    foreach ($name in $names) { Copy-Item (Join-Path $src $name) (Join-Path $dst $name) -Force }
    foreach ($name in $names) {
        & php -l (Join-Path $dst $name)
        if ($LASTEXITCODE -ne 0) { throw "PHP syntax failed: $name" }
    }
    & php artisan view:clear
    if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
    & php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
    $installed = $true
} finally {
    if (-not $installed) {
        foreach ($name in $names) { Copy-Item (Join-Path $backup $name) (Join-Path $dst $name) -Force }
        & php artisan view:clear | Out-Null
        Write-Host 'Installation failed; design restored from backup.' -ForegroundColor Red
    }
}
Write-Host 'HN BLOOM PREMIUM V14 INSTALLED - PDF design only.' -ForegroundColor Green
Write-Host "Backup: $backup"
Write-Host 'No controllers, routes, approvals or CRM database changed.'
