$ErrorActionPreference = 'Stop'
$target = '.\app\Http\Controllers\Admin\HnbChromePdfController.php'
if (-not (Test-Path '.\artisan') -or -not (Test-Path $target)) {
    throw 'Run this script inside the Laravel gestion-commerciale root.'
}

$absolute = (Resolve-Path $target).Path
$original = [System.IO.File]::ReadAllText($absolute)
$old = '$process = new Process([$node, $runner, $htmlPath, $pdfPath], base_path());'
$marker = 'HNB_WINDOWS_NODE_SYSTEMROOT_FIX'
if ($original.Contains($marker)) {
    Write-Host 'Already installed; no change.' -ForegroundColor Green
    exit 0
}
if (-not $original.Contains($old)) {
    throw 'The current Chromium controller differs from the audited version. No changes made.'
}

$backupDir = Join-Path (Get-Location).Path ('backup-hnb-node-env-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
$backup = Join-Path $backupDir 'HnbChromePdfController.php'
Copy-Item $absolute $backup -Force

$new = @'
// HNB_WINDOWS_NODE_SYSTEMROOT_FIX: Node crypto requires SystemRoot in Windows child processes.
            $systemRoot = getenv('SystemRoot') ?: (getenv('WINDIR') ?: 'C:\Windows');
            $process = new Process([$node, $runner, $htmlPath, $pdfPath], base_path(), [
                'SystemRoot' => $systemRoot,
                'WINDIR' => $systemRoot,
            ]);
'@

try {
    $updated = $original.Replace($old, $new.TrimEnd())
    [System.IO.File]::WriteAllText($absolute, $updated, [System.Text.UTF8Encoding]::new($false))
    & php -l $absolute
    if ($LASTEXITCODE -ne 0) { throw 'PHP syntax failed' }
    & php artisan optimize:clear
    if ($LASTEXITCODE -ne 0) { throw 'Laravel optimize:clear failed' }
    Write-Host 'SUCCESS: Node SystemRoot explicitly passed to Chromium PDF renderer.' -ForegroundColor Green
    Write-Host ('Backup: ' + $backup)
    Write-Host 'No workflow, database, Blade or CSS modifications.'
} catch {
    Copy-Item $backup $absolute -Force
    throw
}
