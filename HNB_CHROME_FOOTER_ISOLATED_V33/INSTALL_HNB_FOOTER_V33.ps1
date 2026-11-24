$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$project = Split-Path -Parent $here
$target = Join-Path $project 'tools\pdf-chrome\render.cjs'
$replacement = Join-Path $here 'render.cjs'
$testHtml = Join-Path $here 'test-document.html'
$backupDir = Join-Path $project ('backup-hnb-chrome-footer-v33-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
$testPdf = Join-Path $env:TEMP 'HNB_CHROME_V33_TEST.pdf'

if (-not (Test-Path (Join-Path $project 'artisan'))) { throw 'Run this pack inside the Laravel project root.' }
if (-not (Test-Path $target)) { throw ('Renderer not found: ' + $target) }
if (-not (Test-Path (Join-Path $project 'tools\pdf-chrome\node_modules\puppeteer-core'))) {
  throw 'Puppeteer-core missing. Existing PDF Studio installation is incomplete.'
}

$old = Get-Content $target -Raw
if ($old -match 'HNB_V33_ISOLATED_PRINT_FURNITURE') {
  Write-Host 'V33 already installed. Nothing changed.' -ForegroundColor Yellow
  exit 0
}
if ($old -notmatch 'V29_MULTI_PAGE_PRINT' -or $old -notmatch 'ReturnAsBase64' -or $old -notmatch 'HNB_V31_REMOVE_ORIGINAL_PRINT_FURNITURE') {
  throw 'Renderer is not the expected V29/V31 version. Nothing modified.'
}

$node = (Get-Command node -ErrorAction Stop).Source
& $node --check $replacement
if ($LASTEXITCODE -ne 0) { throw 'New renderer syntax check failed. Nothing modified.' }

New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
Copy-Item $target (Join-Path $backupDir 'render.cjs') -Force
Write-Host ('Backup: ' + $backupDir) -ForegroundColor Cyan
try {
  Copy-Item $replacement $target -Force
  & $node --check $target
  if ($LASTEXITCODE -ne 0) { throw 'Installed renderer syntax check failed.' }
  if (Test-Path $testPdf) { Remove-Item $testPdf -Force }
  & $node $target $testHtml $testPdf
  if ($LASTEXITCODE -ne 0) { throw 'Chromium PDF test failed.' }
  if (-not (Test-Path $testPdf) -or (Get-Item $testPdf).Length -lt 1000) {
    throw 'Chromium generated no usable PDF.'
  }
  Write-Host 'SUCCESS: V33 isolated Chromium print header/footer CSS installed.' -ForegroundColor Green
  Write-Host ('Test PDF: ' + $testPdf) -ForegroundColor Cyan
  Write-Host 'Original quote/invoice data, discounts, TVA, controllers and approvals unchanged.'
} catch {
  Copy-Item (Join-Path $backupDir 'render.cjs') $target -Force
  Write-Host 'ERROR: Original renderer restored from backup.' -ForegroundColor Red
  throw
}
