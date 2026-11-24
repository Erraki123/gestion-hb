$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Run from Laravel project root' }
$folder = '.\storage\app\prospect-imports'
New-Item -ItemType Directory -Force -Path $folder | Out-Null
$pbf = Join-Path $folder 'morocco-latest.osm.pbf'
if (-not (Test-Path $pbf)) {
    Write-Host 'OSM PBF is different from the already downloaded SHP ZIP. Download size ~230+ MB.' -ForegroundColor Yellow
    $choice = Read-Host 'Type OUI to download the official PBF from Geofabrik'
    if ($choice -ne 'OUI') { Write-Host 'Cancelled. Nothing changed.'; exit 0 }
    python .\tools\atlas_pbf_download.py --destination $pbf
    if ($LASTEXITCODE -ne 0) { throw 'PBF download failed; no CRM data changed' }
}
python -c "import osmium"
if ($LASTEXITCODE -ne 0) {
    Write-Host 'Python osmium library required for PBF reading.' -ForegroundColor Yellow
    $install = Read-Host 'Type OUI to install Python osmium package'
    if ($install -ne 'OUI') { exit 1 }
    python -m pip install osmium
    if ($LASTEXITCODE -ne 0) { throw 'Failed to install osmium' }
}
# A dedicated output path avoids overwriting any existing evidence CSV.
$out = Join-Path $folder 'atlas-osm-pbf-evidence.csv'
if (Test-Path $out) {
    Copy-Item $out "$out.backup-$(Get-Date -Format yyyyMMdd-HHmmss)"
    Remove-Item $out
}
python .\tools\atlas_osm_contacts.py --pbf $pbf --output $out
if ($LASTEXITCODE -ne 0) { throw 'PBF contact extraction failed' }
Write-Host "Evidence preview: $out" -ForegroundColor Green
Import-Csv $out | Select-Object -First 10 | Format-Table -AutoSize
Write-Host 'These are OSM tag values, NOT verified contacts. Nothing imported into CRM.'
