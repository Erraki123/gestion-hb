$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Run from Laravel project root.' }
$target = Join-Path $root 'resources\views\pdf\chrome\style.blade.php'
$patch = Join-Path $PSScriptRoot 'PATCH_CHROMIUM_BLACK_GOLD.css'
if (-not (Test-Path $target)) { throw 'Chromium PDF Studio not installed: style.blade.php missing.' }
if (-not (Test-Path $patch)) { throw 'Missing patch file; extract whole ZIP.' }
$old = [System.IO.File]::ReadAllText($target)
if ($old -notmatch '\.brand-row' -or $old -notmatch '\.document-ribbon' -or $old -notmatch '</style>') { throw 'Unexpected Chromium Studio CSS; no changes made.' }
$backupDir = Join-Path $root ('backup-chrome-colors-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Force $backupDir | Out-Null
Copy-Item $target (Join-Path $backupDir 'style.blade.php')
$start = '/* HNB_CHROMIUM_BLACK_GOLD_PATCH_START */'
$end = '/* HNB_CHROMIUM_BLACK_GOLD_PATCH_END */'
$re = '(?s)/\* HNB_CHROMIUM_BLACK_GOLD_PATCH_START \*/.*?/\* HNB_CHROMIUM_BLACK_GOLD_PATCH_END \*/'
$clean = [regex]::Replace($old,$re,'')
$css = [System.IO.File]::ReadAllText($patch)
$new = $clean.Replace('</style>', $css + "`n</style>")
try {
  [System.IO.File]::WriteAllText($target,$new,[System.Text.UTF8Encoding]::new($false))
  php artisan view:clear
  if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
  php artisan view:cache
  if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
  Write-Host 'SUCCESS: Chromium HTML/PDF now uses Black + Gold + White.' -ForegroundColor Green
  Write-Host 'Header proportions adjusted. Other files untouched.'
  Write-Host "Backup: $backupDir"
} catch {
  Copy-Item (Join-Path $backupDir 'style.blade.php') $target -Force
  php artisan view:clear | Out-Null
  throw "RESTORED: $($_.Exception.Message)"
}
