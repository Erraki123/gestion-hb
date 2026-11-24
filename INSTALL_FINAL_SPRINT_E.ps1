$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
python .\APPLY_FINAL_SPRINT_E.py
if ($LASTEXITCODE -ne 0) { throw 'SPRINT E aborted; see error above.' }
