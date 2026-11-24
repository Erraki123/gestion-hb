$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
$source = Join-Path $PSScriptRoot 'PATCH_FINAL_QA_GUARD\SendDocumentJob.php'
$target = Join-Path $root 'app\Jobs\SendDocumentJob.php'
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Run this installer from the Laravel project root.' }
if (-not (Test-Path $target)) { throw 'SendDocumentJob.php missing.' }
if (-not (Test-Path $source)) { throw 'Patch file missing.' }
$current = [System.IO.File]::ReadAllText($target)
if ((-not $current.Contains('DocumentReleaseApproval::class')) -or (-not $current.Contains('Validation directeur absente ou obsolete'))) {
  throw 'Sprint C job implementation does not match expected source. No changes made.'
}
$backupDir = Join-Path $root ('backup-final-qa-guard-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
Copy-Item $target (Join-Path $backupDir 'SendDocumentJob.php') -Force
try {
  Copy-Item $source $target -Force
  & php -l $target
  if ($LASTEXITCODE -ne 0) { throw 'PHP lint failed' }
  Write-Host ('QA GUARD installed. Backup: ' + $backupDir) -ForegroundColor Green
  Write-Host 'No migrations, no outbound messages, no scheduled tasks changed.'
} catch {
  Copy-Item (Join-Path $backupDir 'SendDocumentJob.php') $target -Force
  throw
}
