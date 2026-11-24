$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Run from Laravel project root (artisan missing).' }
if (-not (Test-Path '.\PATCH_ATLAS_6K\tools\atlas_contact_merge.py')) { throw 'Extract ZIP in project root first.' }
python --version
if ($LASTEXITCODE -ne 0) { throw 'Python 3 required' }
python -m unittest discover -s .\PATCH_ATLAS_6K\tests -p 'test_*.py' -v
if ($LASTEXITCODE -ne 0) { throw 'Tests failed; no files changed' }
New-Item -ItemType Directory -Path .\tools -Force | Out-Null
$dest = '.\tools\atlas_contact_merge.py'
if (Test-Path $dest) {
  $backup = "$dest.backup-$(Get-Date -Format yyyyMMdd-HHmmss)"
  Copy-Item $dest $backup
  Write-Host "Backup: $backup"
}
Copy-Item .\PATCH_ATLAS_6K\tools\atlas_contact_merge.py $dest -Force
python .\tools\atlas_contact_merge.py
if ($LASTEXITCODE -ne 0) { throw 'Preview failed' }
Write-Host 'ATLAS 6K installed locally. 0 DB writes, 0 messages, 0 API calls.' -ForegroundColor Green
