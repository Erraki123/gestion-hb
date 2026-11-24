$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
if (-not (Test-Path '.\artisan')) { throw 'Run from Laravel project root.' }
if (-not (Test-Path '.\vendor\autoload.php')) { throw 'Composer dependencies missing.' }
if (-not (Test-Path '.\tests\Feature\HnBloomDirectorWorkflowTest.php')) { throw 'Test file missing.' }
$phpunit = Get-Content '.\phpunit.xml' -Raw
if ($phpunit -notmatch 'name="DB_CONNECTION" value="sqlite"' -or $phpunit -notmatch 'name="DB_DATABASE" value=":memory:"') {
    throw 'Safety stop: phpunit.xml must explicitly configure sqlite :memory:. No tests run.'
}
$old = @{}
foreach ($name in @('APP_ENV','DB_CONNECTION','DB_DATABASE','DB_URL','MAIL_MAILER','QUEUE_CONNECTION','CACHE_STORE','SESSION_DRIVER')) {
    $old[$name] = [Environment]::GetEnvironmentVariable($name, 'Process')
}
try {
    $env:APP_ENV = 'testing'
    $env:DB_CONNECTION = 'sqlite'
    $env:DB_DATABASE = ':memory:'
    $env:DB_URL = ''
    $env:MAIL_MAILER = 'array'
    $env:QUEUE_CONNECTION = 'sync'
    $env:CACHE_STORE = 'array'
    $env:SESSION_DRIVER = 'array'
    & php artisan test --filter=HnBloomDirectorWorkflowTest --stop-on-failure
    if ($LASTEXITCODE -ne 0) { throw 'Functional tests failed; do NOT deploy.' }
    Write-Host 'Workflow tests PASSED on isolated SQLite memory database.' -ForegroundColor Green
} finally {
    foreach ($name in $old.Keys) {
        [Environment]::SetEnvironmentVariable($name, $old[$name], 'Process')
    }
}
