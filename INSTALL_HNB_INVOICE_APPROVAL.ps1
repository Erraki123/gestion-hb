$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Run from Laravel project directory.' }
python .\APPLY_INVOICE_APPROVAL.py
if ($LASTEXITCODE -ne 0) { throw 'Invoice approval installation failed.' }
