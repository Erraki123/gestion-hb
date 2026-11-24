$ErrorActionPreference = 'Continue'
if (-not (Test-Path '.\artisan')) { throw 'Open PowerShell at Laravel project root.' }
$now = Get-Date -Format 'yyyyMMdd-HHmmss'
$out = '.\final-qa-' + $now + '.txt'
'FINAL QA - READ ONLY' | Set-Content -Path $out -Encoding UTF8
'Generated: ' + (Get-Date) | Add-Content $out
function Run-Check($name, $command) {
  ('=== ' + $name + ' ===') | Tee-Object -FilePath $out -Append
  (& cmd.exe /d /c ($command + ' 2>&1')) | Tee-Object -FilePath $out -Append
  ('Exit code: ' + $LASTEXITCODE) | Tee-Object -FilePath $out -Append
}
Run-Check 'PHP' 'php -v'
Run-Check 'Laravel' 'php artisan --version'
Run-Check 'Migration status (read only)' 'php artisan migrate:status --no-ansi'
Run-Check 'Director release routes' 'php artisan route:list --name=documents.release --no-ansi'
Run-Check 'Manual send route' 'php artisan route:list --name=documents.send --no-ansi -v'
Run-Check 'Public document link route' 'php artisan route:list --name=documents.public --no-ansi -v'
Run-Check 'Scoring route' 'php artisan route:list --name=admin.atlas.scoring-v2 --no-ansi'
Run-Check 'Scheduler (read only)' 'php artisan schedule:list --no-ansi'
$files = @('app\Jobs\SendDocumentJob.php','app\Services\DocumentReleaseApproval.php','app\Services\DocumentSender.php','app\Http\Controllers\PublicDocumentController.php','app\Http\Controllers\DocumentSendController.php')
foreach ($file in $files) {
  if (Test-Path $file) { Run-Check ('Syntax ' + $file) ('php -l "' + $file + '"') }
  else { ('MISSING: ' + $file) | Tee-Object -FilePath $out -Append }
}
$job = Get-Content '.\app\Jobs\SendDocumentJob.php' -Raw
$checks = @{
 'Queue approval check' = ($job -match 'canRelease\(')
 'Queue recipient check' = ($job -match 'currentRecipient')
 'Queue approval time check' = ($job -match 'latestRelease')
}
'=== Security guard presence (static inspection only) ===' | Tee-Object -FilePath $out -Append
foreach ($key in $checks.Keys) { ($key + ': ' + $checks[$key]) | Tee-Object -FilePath $out -Append }
('REPORT: ' + (Resolve-Path $out)) | Write-Host -ForegroundColor Cyan
Write-Host 'No tests, migrations, queues, API calls, emails or WhatsApp sends triggered by this QA script.'
