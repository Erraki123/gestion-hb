$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
$source = Join-Path $root 'HNB_PDF_ART_DECO_V6\payload'
$target = Join-Path $root 'resources\views\pdf'
$public = Join-Path $root 'public\images'
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Lancer depuis la racine Laravel (artisan manquant).' }
if (-not (Test-Path (Join-Path $source 'resources\views\pdf\_style.blade.php'))) { throw 'Archive non extraite dans la racine du projet.' }
foreach ($file in @('_header.blade.php','_footer.blade.php','_style.blade.php')) {
    if (-not (Test-Path (Join-Path $target $file))) { throw "PDF source missing: $file" }
}
$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backup = Join-Path $root ("backup-hnb-pdf-v6-" + $timestamp)
New-Item -ItemType Directory -Path $backup -Force | Out-Null
$destFiles = @(
    'resources\views\pdf\_header.blade.php',
    'resources\views\pdf\_footer.blade.php',
    'resources\views\pdf\_style.blade.php',
    'public\images\hnb-v6-gold-fan.png',
    'public\images\hnb-v6-seal.png'
)
$existed=@{}
foreach ($rel in $destFiles) {
    $dst=Join-Path $root $rel
    $existed[$rel] = Test-Path $dst
    if ($existed[$rel]) {
        $backupFile=Join-Path $backup $rel
        New-Item -ItemType Directory -Path (Split-Path $backupFile) -Force | Out-Null
        Copy-Item $dst $backupFile -Force
    }
}
try {
    foreach ($rel in $destFiles) {
        $src=Join-Path $source $rel
        $dst=Join-Path $root $rel
        if (-not (Test-Path $src)) { throw "Source missing: $rel" }
        New-Item -ItemType Directory -Path (Split-Path $dst) -Force | Out-Null
        Copy-Item $src $dst -Force
    }
    foreach ($name in @('_header.blade.php','_footer.blade.php','_style.blade.php')) {
        $file = Join-Path $target $name
        & php -l $file
        if ($LASTEXITCODE -ne 0) { throw "Syntax check failed: $name" }
    }
    & php artisan view:clear
    if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
    & php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
} catch {
    Write-Warning ('Install error: ' + $_.Exception.Message)
    foreach ($rel in $destFiles) {
        $dst=Join-Path $root $rel
        if ($existed[$rel]) { Copy-Item (Join-Path $backup $rel) $dst -Force }
        elseif (Test-Path $dst) { Remove-Item $dst -Force }
    }
    & php artisan view:clear | Out-Null
    throw 'Installation failed. Original design restored.'
}
Write-Host ('SUCCESS: HN BLOOM ART DECO V6 installed. Backup: ' + $backup) -ForegroundColor Green
Write-Host 'No controllers, routes, migrations, data, approvals or outbound messages changed.'
Write-Host 'Generate a NEW PDF from the CRM to see the design.'
