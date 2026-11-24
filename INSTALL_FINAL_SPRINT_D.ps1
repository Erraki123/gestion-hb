$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest
$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Run this inside Laravel root.' }
$rel = @('app/Services/DocumentSender.php', 'app/Http/Controllers/PublicDocumentController.php')
$target1 = Join-Path $root $rel[0]
$original = [System.IO.File]::ReadAllText($target1)
if (-not $original.Contains('DocumentReleaseApproval::class')) { throw 'Sprint C service missing; stopping safely.' }
$backup = Join-Path $root ('backup-final-sprint-d-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
foreach ($file in $rel) {
    $old = Join-Path $root $file
    if (-not (Test-Path $old)) { throw ('Missing file ' + $file) }
    $dest = Join-Path $backup $file
    New-Item -ItemType Directory -Path (Split-Path $dest) -Force | Out-Null
    Copy-Item -LiteralPath $old -Destination $dest -Force
}
try {
    foreach ($file in $rel) {
        Copy-Item -LiteralPath (Join-Path $root ('PATCH_SPRINT_D/' + $file)) -Destination (Join-Path $root $file) -Force
    }
    foreach ($file in $rel) {
        & php -l (Join-Path $root $file)
        if ($LASTEXITCODE -ne 0) { throw ('PHP lint failed: ' + $file) }
    }
    & php artisan route:list --name=documents.public
    if ($LASTEXITCODE -ne 0) { throw 'Public document route unavailable' }
    & php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'Blade validation failed' }
    Write-Host ('SPRINT D HOTFIX INSTALLED. Backup: ' + $backup) -ForegroundColor Green
    Write-Host 'Public document links now require a matching approved fingerprint.'
    Write-Host 'Old public links (without v) now return HTTP 403. Re-send if needed.' -ForegroundColor Yellow
} catch {
    foreach ($file in $rel) { Copy-Item -LiteralPath (Join-Path $backup $file) -Destination (Join-Path $root $file) -Force }
    Write-Warning ('Files restored from ' + $backup)
    throw
}
