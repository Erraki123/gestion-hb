$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Open the Laravel root folder first.' }
$source = '.\PATCH\tools\atlas_osm_geofabrik.py'
if (-not (Test-Path $source)) { throw 'Missing Python ATLAS tool from ZIP.' }
New-Item -ItemType Directory -Force -Path '.\tools' | Out-Null
$target = '.\tools\atlas_osm_geofabrik.py'
if (Test-Path $target) { Copy-Item $target ($target + '.backup-' + (Get-Date -Format yyyyMMddHHmmss)) }
Copy-Item $source $target -Force
Write-Host 'ATLAS Phase 6E installed: offline Geofabrik OSM extraction.' -ForegroundColor Green
Write-Host 'No DB or HTTP changes. Run .\ATLAS_DISCOVER_GEOFABRIK.ps1 to download once and preview.'
