$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Run this script in Laravel project root.' }
$python = Get-Command py -ErrorAction SilentlyContinue
if ($python) { $pyCmd = 'py'; $pyPrefix = @('-3') }
else {
    $python = Get-Command python -ErrorAction SilentlyContinue
    if (-not $python) { throw 'Python 3 required. Install Python 3 before running this extraction.' }
    $pyCmd = 'python'; $pyPrefix = @()
}
$tool = '.\tools\atlas_osm_geofabrik.py'
$dir = '.\storage\app\prospect-imports'
New-Item -ItemType Directory -Force -Path $dir | Out-Null
$out = Join-Path $dir 'atlas-osm-maroc.csv'
$archive = Join-Path $dir 'morocco-latest-free.shp.zip'
if (-not (Test-Path $archive)) {
    Write-Host 'The official Geofabrik dataset is about 500 MB; download only with your consent.' -ForegroundColor Yellow
    $agree = Read-Host 'Type OUI to download once, or any other key to cancel'
    if ($agree -ne 'OUI') { Write-Host 'Cancelled. No files changed.'; exit 0 }
}
& $pyCmd @pyPrefix $tool --archive $archive --output $out --download --per-city 40
if ($LASTEXITCODE -ne 0) { throw 'Geofabrik extraction failed. CRM left untouched.' }
Write-Host '--- PREVIEW: no CRM data changed ---' -ForegroundColor Cyan
php artisan prospects:import-csv atlas-osm-maroc.csv --source=geofabrik_osm --dry-run --limit=500
if ($LASTEXITCODE -ne 0) { throw 'Import preview failed. CRM left untouched.' }
Write-Host 'Dataset prepared. Review preview. To insert real OSM candidates, run:' -ForegroundColor Green
Write-Host 'php artisan prospects:import-csv atlas-osm-maroc.csv --source=geofabrik_osm --limit=500'
Write-Host 'php artisan atlas:radar --limit=30'
