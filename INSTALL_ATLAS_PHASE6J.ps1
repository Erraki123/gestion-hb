$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Open PowerShell in Laravel project root.' }
if (-not (Test-Path '.\PATCH_ATLAS_6J\tools\atlas_contact_audit.py')) { throw 'Missing patch files.' }
New-Item -ItemType Directory -Force '.\tools' | Out-Null
$backup = 'backup-atlas6j-' + (Get-Date -Format 'yyyyMMdd-HHmmss')
if (Test-Path '.\tools\atlas_contact_audit.py') {
  New-Item -ItemType Directory -Force $backup | Out-Null
  Copy-Item '.\tools\atlas_contact_audit.py' (Join-Path $backup 'atlas_contact_audit.py')
}
Copy-Item '.\PATCH_ATLAS_6J\tools\atlas_contact_audit.py' '.\tools\atlas_contact_audit.py' -Force
python -m unittest discover -s '.\PATCH_ATLAS_6J\tests' -p 'test_atlas_contact_audit.py' -v
if ($LASTEXITCODE -ne 0) { throw 'ATLAS tests failed.' }
python '.\tools\atlas_contact_audit.py' --provider none
if ($LASTEXITCODE -ne 0) { throw 'ATLAS audit failed.' }
Write-Host 'ATLAS Phase 6J prepared. No API calls. No DB changes. No messages.' -ForegroundColor Green
