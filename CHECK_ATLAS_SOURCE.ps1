$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location -LiteralPath $root
if (-not (Test-Path '.\artisan')) { throw 'Laravel project not found' }
python '.\tools\atlas_source_refresh.py' --project $root --check
if ($LASTEXITCODE -ne 0) { throw 'ATLAS source metadata check failed' }
