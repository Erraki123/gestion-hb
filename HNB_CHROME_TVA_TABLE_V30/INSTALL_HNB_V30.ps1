$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Run from Laravel project root' }
python '.\HNB_CHROME_TVA_TABLE_V30\APPLY_HNB_V30.py'
if ($LASTEXITCODE -ne 0) { throw 'V30 installation failed or not compatible' }
