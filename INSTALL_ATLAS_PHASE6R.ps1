$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location -LiteralPath $root
if (-not (Test-Path '.\artisan')) { throw 'Laravel project not found' }
if (-not (Test-Path '.\tools\atlas_osm_geofabrik.py')) { throw 'Phase 6E extractor missing' }
python -m unittest discover -s '.\tests' -p 'test_atlas_source_refresh.py' -v
if ($LASTEXITCODE -ne 0) { throw '6R tests failed' }
Write-Host 'Phase 6R installed. No new scheduled tasks, no network calls, no CRM writes.' -ForegroundColor Green
Write-Host 'Next: powershell -ExecutionPolicy Bypass -File .\CHECK_ATLAS_SOURCE.ps1'
