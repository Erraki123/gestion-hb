$ErrorActionPreference = 'Stop'
$project = (Get-Location).Path
if (-not (Test-Path '.\artisan')) { throw 'Run from Laravel project root.' }
$source = Join-Path $PSScriptRoot 'render.cjs'
$target = Join-Path $project 'tools\pdf-chrome\render.cjs'
if (-not (Test-Path $target)) { throw 'Chromium renderer is missing.' }

$previous = [IO.File]::ReadAllText($target)
if ($previous.Contains('V29_MULTI_PAGE_PRINT')) {
    Write-Host 'V29 is already installed.' -ForegroundColor Green
    exit 0
}
if (-not ($previous.Contains('ReturnAsBase64') -and $previous.Contains('Page.printToPDF'))) {
    throw 'Unexpected renderer version. Existing file not changed. Please inspect manually.'
}
$backupDir = Join-Path $project ('backup-hnb-chrome-multipage-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
Copy-Item $target (Join-Path $backupDir 'render.cjs')

try {
    Copy-Item $source $target -Force
    & node --check $target
    if ($LASTEXITCODE -ne 0) { throw 'JavaScript syntax check failed.' }

    # Test using a representative local document HTML (no network and no CRM writes).
    $sample = Join-Path $PSScriptRoot 'test-document.html'
    $pdf = Join-Path $env:TEMP 'HNB_CHROME_MULTIPAGE_V29_TEST.pdf'
    if (Test-Path $pdf) { Remove-Item $pdf -Force }
    & node $target $sample $pdf
    if ($LASTEXITCODE -ne 0) { throw 'Chromium sample render failed.' }
    if ((-not (Test-Path $pdf)) -or ((Get-Item $pdf).Length -lt 1000)) {
        throw 'PDF sample not created.'
    }
    Write-Host 'SUCCESS: repeated Header/Footer Chromium print enabled.' -ForegroundColor Green
    Write-Host ('Backup: ' + $backupDir)
    Write-Host ('Local sample PDF: ' + $pdf)
    Write-Host 'No controllers, roles, pricing, approvals or DB changed.'
} catch {
    Copy-Item (Join-Path $backupDir 'render.cjs') $target -Force
    throw ('V29 installation failed; previous renderer restored. ' + $_.Exception.Message)
}
