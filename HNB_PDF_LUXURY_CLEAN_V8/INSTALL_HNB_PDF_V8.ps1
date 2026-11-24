$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Ouvrir PowerShell dans le dossier Laravel contenant artisan.' }
$src = Join-Path $root 'HNB_PDF_LUXURY_CLEAN_V8\payload'
$rels = @(
 'resources\views\pdf\_header.blade.php',
 'resources\views\pdf\_footer.blade.php',
 'resources\views\pdf\_style.blade.php',
 'public\images\hnb-luxury-v8-art.png'
)
foreach ($rel in $rels) { if (-not (Test-Path (Join-Path $src $rel))) { throw "Package incomplet: $rel" } }
$backup = Join-Path $root ('backup-pdf-v8-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
$existed = @{}
foreach ($rel in $rels) {
    $dest = Join-Path $root $rel
    $existed[$rel] = Test-Path $dest
    if ($existed[$rel]) {
        $backupFile = Join-Path $backup $rel
        New-Item -ItemType Directory -Path (Split-Path $backupFile) -Force | Out-Null
        Copy-Item $dest $backupFile -Force
    }
}
try {
    foreach ($rel in $rels) {
        $dest = Join-Path $root $rel
        New-Item -ItemType Directory -Path (Split-Path $dest) -Force | Out-Null
        Copy-Item (Join-Path $src $rel) $dest -Force
    }
    foreach ($rel in $rels[0..2]) {
        & php -l (Join-Path $root $rel)
        if ($LASTEXITCODE -ne 0) { throw "Erreur PHP: $rel" }
    }
    & php artisan view:clear
    if ($LASTEXITCODE -ne 0) { throw 'Laravel view:clear failed' }
    & php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'Laravel view:cache failed' }
} catch {
    Write-Warning ('Erreur: ' + $_.Exception.Message)
    foreach ($rel in $rels) {
        $dest = Join-Path $root $rel
        if ($existed[$rel]) { Copy-Item (Join-Path $backup $rel) $dest -Force }
        elseif (Test-Path $dest) { Remove-Item $dest -Force }
    }
    & php artisan view:clear | Out-Null
    throw 'Installation interrompue. Fichiers restaures.'
}
Write-Host 'SUCCESS: HNB PDF Luxury Clean V8 installed.' -ForegroundColor Green
Write-Host ('Backup: ' + $backup)
Write-Host 'Design only: no DB, controller, route or workflow change.'
