# HN BLOOM - Final Sprint Preparation (source only)
# Windows PowerShell 5.1 compatible. No source changes, no database writes.
$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Run this script from the Laravel project root.' }
$root = (Get-Location).Path
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$outDir = Join-Path $root 'atlas-final-sprint'
New-Item -ItemType Directory -Path $outDir -Force | Out-Null
$stage = Join-Path $env:TEMP ('hnbloom-source-' + $stamp)
New-Item -ItemType Directory -Path $stage -Force | Out-Null
$report = Join-Path $outDir ('audit-' + $stamp + '.txt')
'ATLAS FINAL SPRINT - LOCAL SOURCE PREP' | Set-Content $report -Encoding UTF8
('Timestamp: ' + $stamp) | Add-Content $report
('Root: ' + $root) | Add-Content $report
function RunReadOnly([string]$label, [string]$command) {
    ('=== ' + $label + ' ===') | Add-Content $report
    try {
        $res = & cmd.exe /d /c ($command + ' 2>&1') | Out-String
        $res | Add-Content $report
        Write-Host ($label + ' completed') -ForegroundColor Cyan
    } catch {
        ('ERROR: ' + $_.Exception.Message) | Add-Content $report
        Write-Warning ($label + ' failed: ' + $_.Exception.Message)
    }
}
RunReadOnly 'PHP version' 'php -v'
RunReadOnly 'Laravel version' 'php artisan --version'
RunReadOnly 'Routes' 'php artisan route:list --compact'
RunReadOnly 'Migrations status' 'php artisan migrate:status'
RunReadOnly 'Scheduled commands' 'php artisan schedule:list'
RunReadOnly 'Composer validation' 'composer validate --no-check-publish'
$dirs = @('app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'tests', 'tools')
foreach ($dir in $dirs) {
    $source = Join-Path $root $dir
    if (Test-Path $source) {
        $dest = Join-Path $stage $dir
        New-Item -ItemType Directory -Path $dest -Force | Out-Null
        & robocopy $source $dest /E /XD node_modules vendor .git cache logs /XF *.sqlite *.sqlite3 *.db *.log *.bak *.backup *.zip *.pbf *.csv *.jsonl *.pem *.key *.p12 *.pfx > $null
        if ($LASTEXITCODE -ge 8) { throw ('File copy failed for ' + $dir) }
    }
}
$files = @('artisan','composer.json','composer.lock','package.json','package-lock.json','vite.config.js','vite.config.ts','phpunit.xml','postcss.config.js','tailwind.config.js','README.md')
foreach ($file in $files) { if (Test-Path (Join-Path $root $file)) { Copy-Item (Join-Path $root $file) (Join-Path $stage $file) -Force } }
# Exclude runtime artifacts and never copy user data, secrets, vendor, .git or local media.
$zip = Join-Path $outDir ('HN_BLOOM_SOURCE_ACTUEL_' + $stamp + '.zip')
Compress-Archive -Path (Join-Path $stage '*') -DestinationPath $zip -Force
Remove-Item $stage -Recurse -Force
Write-Host ''
Write-Host 'SOURCE PACKAGE:' -ForegroundColor Green
Write-Host $zip
Write-Host 'AUDIT REPORT:' -ForegroundColor Green
Write-Host $report
Write-Host 'IMPORTANT: Review the archive for any hardcoded secrets before sharing.' -ForegroundColor Yellow
