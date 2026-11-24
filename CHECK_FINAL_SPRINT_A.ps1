$ErrorActionPreference = 'Continue'
if (-not (Test-Path '.\artisan')) { throw 'Run in Laravel project root.' }
$path = '.\atlas-final-sprint'
New-Item -ItemType Directory -Force -Path $path | Out-Null
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$report = Join-Path $path "qa-a-$stamp.txt"
function Run-Check($label, $cmd) {
    Add-Content $report "`r`n==== $label ===="
    Write-Host "Checking: $label" -ForegroundColor Cyan
    $out = & cmd.exe /c "$cmd 2>&1"
    $code = $LASTEXITCODE
    $out | Out-File -Append -FilePath $report -Encoding utf8
    Add-Content $report "Exit code: $code"
    Write-Host "Exit code: $code"
}
Run-Check 'PHP' 'php -v'
Run-Check 'Laravel' 'php artisan --version'
Run-Check 'Routes (no --compact)' 'php artisan route:list'
Run-Check 'Migrations' 'php artisan migrate:status'
Run-Check 'Scheduler' 'php artisan schedule:list'
Run-Check 'Composer' 'composer validate --no-check-publish'
Run-Check 'Tests atlas' 'php artisan test --filter=Atlas'
Run-Check 'Tests access' 'php artisan test --filter=CrmAiAccessTest'
Run-Check 'Tests messenger' 'php artisan test --filter=UltraMsg'
Write-Host "Report: $report" -ForegroundColor Green
Write-Host 'No deployment or messages triggered by this checker. Tests may write to their configured test database: use an isolated test DB.' -ForegroundColor Yellow
