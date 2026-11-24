$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Open PowerShell in gestion-commerciale project root.' }
$installer = Join-Path $PSScriptRoot 'APPLY_HNB_ALL_TYPES.py'
python $installer
if ($LASTEXITCODE -ne 0) { throw 'Installation failed. Review error; backups have been restored.' }
