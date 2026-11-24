# Run from the Laravel root, where artisan is located.
$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Ouvrez PowerShell dans le dossier Laravel (artisan).' }
if (-not (Test-Path '.\app\Http\Controllers\CrmAiUnifiedController.php')) { throw 'Installez Phase 4C avant Phase 4D.' }
$zip = Join-Path $env:USERPROFILE 'Downloads\HN_BLOOM_Phase4D_IA_Flottante_Admin.zip'
if (-not (Test-Path $zip)) { throw "ZIP introuvable: $zip" }
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = "backup-phase4D-$stamp"
New-Item -ItemType Directory -Path $backup -Force | Out-Null
Copy-Item '.\routes\web.php' "$backup\web.php"
Copy-Item '.\resources\views\layouts\app.blade.php' "$backup\app.blade.php"
Expand-Archive $zip -DestinationPath . -Force
$routeLine = "require __DIR__ . '/ai_floating_phase4d.php';"
$routes = '.\routes\web.php'
$routeText = Get-Content $routes -Raw
if (-not $routeText.Contains($routeLine)) {
    Add-Content $routes "`r`n$routeLine" -Encoding UTF8
}
$layout = '.\resources\views\layouts\app.blade.php'
$layoutText = [System.IO.File]::ReadAllText((Resolve-Path $layout))
$include = "@include('layouts._ai_floating')"
if (-not $layoutText.Contains($include)) {
    $anchor = "@include('layouts._search')"
    if (-not $layoutText.Contains($anchor)) { throw 'Point d insertion introuvable. Restaurer le layout depuis backup-phase4D.' }
    $layoutText = $layoutText.Replace($anchor, "$anchor`r`n    $include")
    $utf8 = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText((Resolve-Path $layout), $layoutText, $utf8)
}
php artisan optimize:clear
if ($LASTEXITCODE -ne 0) { throw 'optimize:clear failed' }
php artisan route:list --path=admin/ai-floating
if ($LASTEXITCODE -ne 0) { throw 'route:list failed' }
php artisan view:cache
if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
Write-Host "Phase 4D installee. Sauvegarde: $backup" -ForegroundColor Green
