$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Run from Laravel project root (artisan missing)' }
if (-not (Test-Path '.\PATCH_ATLAS_6L\tools\atlas_osm_contacts.py')) { throw 'Extract ZIP into Laravel root first' }
python -m unittest discover -s .\PATCH_ATLAS_6L\tests -p 'test_*.py' -v
if ($LASTEXITCODE -ne 0) { throw 'ATLAS 6L tests failed; nothing installed' }
New-Item -ItemType Directory -Path .\tools -Force | Out-Null
$dest = '.\tools\atlas_osm_contacts.py'
if (Test-Path $dest) { Copy-Item $dest "$dest.backup-$(Get-Date -Format yyyyMMdd-HHmmss)" }
Copy-Item '.\PATCH_ATLAS_6L\tools\atlas_osm_contacts.py' $dest -Force
Copy-Item '.\PATCH_ATLAS_6L\tools\atlas_pbf_download.py' '.\tools\atlas_pbf_download.py' -Force
Write-Host 'ATLAS 6L installed. No database changes, no network calls, no messages.' -ForegroundColor Green
Write-Host 'Run .\RUN_ATLAS_PHASE6L.ps1 when ready to fetch PBF and review available OSM contacts.'
