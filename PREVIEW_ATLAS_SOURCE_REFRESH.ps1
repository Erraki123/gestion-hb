$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location -LiteralPath $root
if (-not (Test-Path '.\artisan')) { throw 'Laravel project not found' }
Write-Host 'This fetches ~470 MB ONLY for a staged preview; does not modify existing atlas CSV or CRM.' -ForegroundColor Yellow
$answer = Read-Host 'Type OUI to download new source snapshot'
if ($answer -cne 'OUI') { Write-Host 'Cancelled. Nothing changed.'; exit 0 }
python '.\tools\atlas_source_refresh.py' --project $root --stage
if ($LASTEXITCODE -ne 0) { throw 'ATLAS staged download failed; existing source untouched' }
