$ErrorActionPreference='Stop'
if (-not (Test-Path '.\artisan')) { throw 'Execute from Laravel project root.' }
python '.\HNB_CHROMIUM_APPROVAL_REMISE_V28\APPLY_HNB_V28.py'
if ($LASTEXITCODE -ne 0) { throw 'V28 stopped; see diagnostics above.' }
