$ErrorActionPreference = 'Stop'
Set-Location (Get-Location).Path
python (Join-Path $PSScriptRoot 'APPLY_HNB_HEADER_V23.py')
if ($LASTEXITCODE -ne 0) { throw 'Header installation failed. See error above.' }
