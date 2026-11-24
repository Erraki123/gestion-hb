$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Place-toi dans le dossier Laravel avec artisan.' }
$script = Join-Path $PSScriptRoot 'FIX_PDF_PAGINATION_V12.py'
if (-not (Test-Path $script)) { throw 'Script Python manquant.' }
python $script
if ($LASTEXITCODE -ne 0) { throw 'V12 installation failed. See message above.' }
