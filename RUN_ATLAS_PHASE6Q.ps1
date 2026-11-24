$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location -LiteralPath $root
if (-not (Test-Path '.\artisan')) { throw 'Laravel project root not found' }
python '.\tools\atlas_daily_monitor.py' --project $root
if ($LASTEXITCODE -ne 0) { throw 'ATLAS 6Q failed. Check storage/app/atlas-local-logs' }
