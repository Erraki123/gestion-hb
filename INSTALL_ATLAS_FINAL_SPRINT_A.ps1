$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
if (-not (Test-Path -LiteralPath (Join-Path $root 'artisan'))) {
    throw 'Run this installer from the Laravel project root.'
}
$patch = Join-Path $PSScriptRoot 'PATCH'
$targets = @(
    'app/Http/Controllers/Admin/AtlasScoreV2Controller.php',
    'routes/atlas_score_v2.php',
    'resources/views/admin/atlas/score-v2.blade.php'
)
$routeRel = 'routes/web.php'
$viewRel = 'resources/views/prospects/_discovery_review.blade.php'
$routeFile = Join-Path $root $routeRel
$viewFile = Join-Path $root $viewRel
$utf8 = New-Object System.Text.UTF8Encoding($false)
if (-not (Test-Path -LiteralPath $routeFile)) { throw 'Missing routes/web.php.' }
if (-not (Test-Path -LiteralPath $viewFile)) { throw 'Missing Prospection Discovery view.' }
foreach ($item in $targets) {
    if (-not (Test-Path -LiteralPath (Join-Path $patch $item))) {
        throw "Missing patch file: $item"
    }
}
# Check compatibility before changing any file.
$oldView = [System.IO.File]::ReadAllText($viewFile)
$needle = "@include('prospects._atlas_radar')"
if ((-not $oldView.Contains('admin.atlas.scoring-v2')) -and (-not $oldView.Contains($needle))) {
    throw 'Could not find the expected Prospection insertion marker; no files changed.'
}
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = Join-Path $root "backup-final-sprint-a-$stamp"
New-Item -ItemType Directory -Force -Path $backup | Out-Null
$existing = @()
foreach ($item in (@($routeRel, $viewRel) + $targets)) {
    $dest = Join-Path $root $item
    if (Test-Path -LiteralPath $dest) {
        $existing += $item
        $saved = Join-Path $backup $item
        New-Item -ItemType Directory -Force -Path (Split-Path -Parent $saved) | Out-Null
        Copy-Item -LiteralPath $dest -Destination $saved -Force
    }
}
try {
    foreach ($item in $targets) {
        $dest = Join-Path $root $item
        New-Item -ItemType Directory -Force -Path (Split-Path -Parent $dest) | Out-Null
        Copy-Item -LiteralPath (Join-Path $patch $item) -Destination $dest -Force
    }
    $routeText = [System.IO.File]::ReadAllText($routeFile)
    $routeRequire = "require __DIR__ . '/atlas_score_v2.php';"
    if (-not $routeText.Contains($routeRequire)) {
        $routeText = $routeText.TrimEnd() + "`r`n`r`n" + $routeRequire + "`r`n"
        [System.IO.File]::WriteAllText($routeFile, $routeText, $utf8)
    }
    $viewText = [System.IO.File]::ReadAllText($viewFile)
    $link = '<a href="{{ route(''admin.atlas.scoring-v2'') }}" class="inline-flex rounded-lg border border-amber-400 bg-stone-950 px-3 py-2 text-xs font-semibold text-amber-100">ATLAS Smart Scoring V2</a>'
    if (-not $viewText.Contains('admin.atlas.scoring-v2')) {
        $viewText = $viewText.Replace($needle, $link + "`r`n      " + $needle)
        [System.IO.File]::WriteAllText($viewFile, $viewText, $utf8)
    }
    foreach ($item in @('app/Http/Controllers/Admin/AtlasScoreV2Controller.php','routes/atlas_score_v2.php','routes/web.php')) {
        & php -l (Join-Path $root $item)
        if ($LASTEXITCODE -ne 0) { throw "PHP syntax failed: $item" }
    }
    & php artisan optimize:clear
    if ($LASTEXITCODE -ne 0) { throw 'Cache clear failed.' }
    & php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'Blade compilation failed.' }
    & php artisan route:list --name=admin.atlas.scoring-v2
    if ($LASTEXITCODE -ne 0) { throw 'Route verification failed.' }
    Write-Host "SPRINT A INSTALLED. Backup: $backup" -ForegroundColor Green
    Write-Host 'Open /admin/atlas/scoring-v2 as Admin.' -ForegroundColor Cyan
    Write-Host 'No migrations, CRM writes or outbound messages.'
} catch {
    Write-Warning 'Installation failed. Restoring backed-up files.'
    foreach ($item in (@($routeRel, $viewRel) + $targets)) {
        $dest = Join-Path $root $item
        if ($existing -contains $item) {
            Copy-Item -LiteralPath (Join-Path $backup $item) -Destination $dest -Force
        } elseif (Test-Path -LiteralPath $dest) {
            Remove-Item -LiteralPath $dest -Force
        }
    }
    throw
}
