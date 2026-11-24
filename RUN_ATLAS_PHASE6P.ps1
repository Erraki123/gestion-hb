$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Run from Laravel project root' }
$folder = '.\storage\app\prospect-imports'
$source = Join-Path $folder 'atlas-osm-maroc.csv'
if (-not (Test-Path $source)) { throw 'Missing OSM CSV; extract the dataset first' }
if (-not (Test-Path '.\tools\atlas_smart_select.py')) { throw 'Missing Phase6F script' }
if (-not (Test-Path '.\tools\atlas_contact_merge.py')) { throw 'Missing Phase6K script' }
# Existing imported candidate file is read-only; existing contact report is backed up.
$review = Join-Path $folder 'atlas-contact-review.csv'
if (Test-Path $review) {
  Copy-Item $review "$review.backup-$(Get-Date -Format yyyyMMdd-HHmmss)" -ErrorAction Stop
}
python '.\tools\atlas_smart_select.py' --source $source --output-dir $folder --limit 60
if ($LASTEXITCODE -ne 0) { throw 'Smart Selection failed' }
$evidence = Join-Path $folder 'atlas-osm-pbf-evidence.csv'
if (Test-Path $evidence) {
  python '.\tools\atlas_contact_merge.py' --candidates (Join-Path $folder 'atlas-smart-preselection.csv') --evidence $evidence --output $review
} else {
  Write-Host 'OSM contact evidence missing. Running candidate-only digest.' -ForegroundColor Yellow
}
if ($LASTEXITCODE -ne 0) { throw 'Contact matching failed' }
python '.\tools\atlas_local_digest.py'
if ($LASTEXITCODE -ne 0) { throw 'Digest failed' }
Write-Host 'ATLAS local review finished. No DB writes, no outreach, no new network requests.' -ForegroundColor Green
