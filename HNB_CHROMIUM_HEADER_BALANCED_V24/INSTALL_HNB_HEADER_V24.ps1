$ErrorActionPreference = 'Stop'
python (Join-Path $PSScriptRoot 'APPLY_HNB_HEADER_V24.py')
if ($LASTEXITCODE -ne 0) { throw 'V24 installation failed' }
