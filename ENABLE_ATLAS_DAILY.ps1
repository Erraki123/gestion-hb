# Explicit opt-in. This task runs ONLY while the user is logged in.
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Laravel project root missing' }
$name = 'HN_BLOOM_ATLAS_LOCAL_MONITOR'
$actionArgs = '-NoProfile -ExecutionPolicy Bypass -File "' + (Join-Path $root 'RUN_ATLAS_PHASE6Q.ps1') + '"'
$action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument $actionArgs -WorkingDirectory $root
$trigger = New-ScheduledTaskTrigger -Daily -At '09:30'
$user = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
$principal = New-ScheduledTaskPrincipal -UserId $user -LogonType Interactive -RunLevel Limited
$existing = Get-ScheduledTask -TaskName $name -ErrorAction SilentlyContinue
if ($existing) {
  Write-Host 'Task already exists. No changes made.' -ForegroundColor Yellow
  exit 0
}
Register-ScheduledTask -TaskName $name -Action $action -Trigger $trigger -Principal $principal -Description 'Offline HN BLOOM ATLAS monitor. No Internet discovery or CRM writes.' | Out-Null
Write-Host 'Task enabled: daily at 09:30 local Windows time while logged in.' -ForegroundColor Green
Write-Host 'To disable: powershell -ExecutionPolicy Bypass -File .\DISABLE_ATLAS_DAILY.ps1'
