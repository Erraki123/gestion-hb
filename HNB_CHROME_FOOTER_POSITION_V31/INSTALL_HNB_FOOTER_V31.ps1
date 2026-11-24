$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Execute this installer in Laravel project root.' }
$target = Join-Path (Get-Location) 'tools\pdf-chrome\render.cjs'
if (-not (Test-Path $target)) { throw 'Chromium renderer missing.' }
$source = [IO.File]::ReadAllText($target)
$marker = 'HNB_V31_REMOVE_ORIGINAL_PRINT_FURNITURE'
if ($source.Contains($marker)) { Write-Host 'V31 already installed.' -ForegroundColor Green; exit 0 }
$needle = @'
    if (!layout.header || !layout.footer) {
      throw new Error('Missing .masthead or .page-footer; source template unsupported.');
    }
'@
$replacement = @'
    if (!layout.header || !layout.footer) {
      throw new Error('Missing .masthead or .page-footer; source template unsupported.');
    }
    // HNB_V31_REMOVE_ORIGINAL_PRINT_FURNITURE
    // Keep the copied branded header/footer only in Chromium print templates.
    await page.evaluate(() => {
      document.querySelectorAll('header.masthead, footer.page-footer').forEach(el => el.remove());
    });
'@
if (-not $source.Contains('V29_MULTI_PAGE_PRINT') -or -not $source.Contains('ReturnAsBase64')) { throw 'Unexpected renderer: V29 and Base64 markers absent. No changes.' }
if (-not $source.Contains($needle)) { throw 'Expected renderer structure not found. No changes.' }
$backupDir = Join-Path (Get-Location) ('backup-hnb-v31-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Force $backupDir | Out-Null
Copy-Item $target (Join-Path $backupDir 'render.cjs') -Force
try {
  [IO.File]::WriteAllText($target, $source.Replace($needle,$replacement), [Text.UTF8Encoding]::new($false))
  & node --check $target
  if ($LASTEXITCODE -ne 0) { throw 'Node syntax check failed' }
  $testHtml = Join-Path $PSScriptRoot 'test-document.html'
  $testPdf = Join-Path $env:TEMP 'HNB_V31_FOOTER_TEST.pdf'
  if (Test-Path $testPdf) { Remove-Item $testPdf -Force }
  & node $target $testHtml $testPdf
  if ($LASTEXITCODE -ne 0 -or -not (Test-Path $testPdf) -or (Get-Item $testPdf).Length -lt 1000) { throw 'PDF smoke test failed' }
  Write-Host 'SUCCESS: V31 Footer Print Placement installed' -ForegroundColor Green
  Write-Host "Test PDF: $testPdf"
  Write-Host "Backup: $backupDir"
  Write-Host 'No Blade, layout, tax, approval or database changes.'
} catch {
  Copy-Item (Join-Path $backupDir 'render.cjs') $target -Force
  throw ('V31 failed; original renderer restored. '+$_.Exception.Message)
}
