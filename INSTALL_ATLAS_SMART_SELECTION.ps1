$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Ouvrez PowerShell dans le dossier Laravel.' }
$source = '.\storage\app\prospect-imports\atlas-osm-maroc.csv'
if (-not (Test-Path $source)) { throw 'CSV source absent. Lancez extraction Geofabrik dabord.' }
python '.\tools\atlas_smart_select.py' --source $source --output-dir '.\storage\app\prospect-imports' --limit 60
if ($LASTEXITCODE -ne 0) { throw 'ATLAS Smart Selection a echoue.' }
Write-Host "`n--- APERCU 15 PRIORITES ---" -ForegroundColor Cyan
Import-Csv '.\storage\app\prospect-imports\atlas-smart-review.csv' |
 Select-Object -First 15 company,city,sector_estime,priorite_heuristique,points_meme_nom_ville |
 Format-Table -AutoSize
Write-Host "`nPour previsualiser (sans importer):" -ForegroundColor Yellow
Write-Host 'php artisan prospects:import-csv atlas-smart-preselection.csv --source=atlas_osm_selection --dry-run'
