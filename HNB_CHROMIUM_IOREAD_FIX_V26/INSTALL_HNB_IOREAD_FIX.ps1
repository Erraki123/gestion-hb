$ErrorActionPreference = 'Stop'
$target = Join-Path (Get-Location) 'tools\pdf-chrome\render.cjs'
$source = Join-Path $PSScriptRoot 'render.cjs'
if (-not (Test-Path 'artisan')) { throw 'Run this from the Laravel project root.' }
if (-not (Test-Path $target)) { throw "Not found: $target" }
$backupDir = Join-Path (Get-Location) ('backup-hnb-chrome-ioread-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
Copy-Item $target (Join-Path $backupDir 'render.cjs') -Force
try {
  Copy-Item $source $target -Force
  & node --check $target
  if ($LASTEXITCODE -ne 0) { throw 'Node syntax check failed.' }
  $smokeRoot = Join-Path $env:TEMP 'hnb-chrome-v26-test'
  New-Item -ItemType Directory -Path $smokeRoot -Force | Out-Null
  $html = Join-Path $smokeRoot 'test.html'
  $pdf = Join-Path $smokeRoot 'test.pdf'
  [System.IO.File]::WriteAllText($html, '<!doctype html><html><body><h1>HN BLOOM PDF TEST</h1></body></html>')
  if (Test-Path $pdf) { Remove-Item $pdf -Force }
  & node $target $html $pdf
  if ($LASTEXITCODE -ne 0 -or -not (Test-Path $pdf)) { throw 'Chromium smoke test failed.' }
  if ((Get-Item $pdf).Length -lt 500) { throw 'PDF output is unexpectedly small.' }
  Write-Host 'SUCCESS: Renderer now uses CDP base64, no IO.read stream.' -ForegroundColor Green
  Write-Host ('Backup: ' + $backupDir)
  Write-Host ('Test PDF: ' + $pdf)
  Write-Host 'No Laravel routes, approval rules, CSS, data or controllers changed.'
} catch {
  Copy-Item (Join-Path $backupDir 'render.cjs') $target -Force
  Write-Host 'The old renderer was restored.' -ForegroundColor Yellow
  throw
}
