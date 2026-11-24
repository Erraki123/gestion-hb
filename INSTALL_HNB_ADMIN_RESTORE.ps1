$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
python .\INSTALL_HNB_ADMIN_RESTORE.py
if ($LASTEXITCODE -ne 0) { throw 'Admin restore aborted. Read error above.' }
