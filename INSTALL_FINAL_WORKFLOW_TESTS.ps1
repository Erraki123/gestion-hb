$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
$src = Join-Path $PSScriptRoot 'PATCH_FINAL_WORKFLOW_TESTS\tests\Feature\HnBloomDirectorWorkflowTest.php'
$dst = Join-Path $PSScriptRoot 'tests\Feature\HnBloomDirectorWorkflowTest.php'
if (-not (Test-Path (Join-Path $PSScriptRoot 'artisan'))) { throw 'Not Laravel root' }
if (-not (Test-Path $src)) { throw 'Patch file missing' }
if (Test-Path $dst) {
    $b = Join-Path $PSScriptRoot ('backup-final-workflow-test-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.php')
    Copy-Item $dst $b
    Write-Host ('Backup: ' + $b)
}
Copy-Item $src $dst -Force
& php -l $dst
if ($LASTEXITCODE -ne 0) { throw 'PHP syntax check failed' }
Write-Host 'Tests installed. No app code, database, scheduler or messaging changed.' -ForegroundColor Green
Write-Host 'Run: powershell -ExecutionPolicy Bypass -File .\RUN_FINAL_WORKFLOW_TESTS.ps1'
