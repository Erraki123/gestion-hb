$ErrorActionPreference = 'Stop'
python (Join-Path $PSScriptRoot 'APPLY_HNB_HEADER_V25.py')
if ($LASTEXITCODE -ne 0) { throw 'Header V25 installation failed.' }
