$ErrorActionPreference = 'Stop'
$name = 'HN_BLOOM_ATLAS_LOCAL_MONITOR'
$task = Get-ScheduledTask -TaskName $name -ErrorAction SilentlyContinue
if ($task) {
  Unregister-ScheduledTask -TaskName $name -Confirm:$false
  Write-Host 'ATLAS automatic task removed; project files retained.' -ForegroundColor Green
} else {
  Write-Host 'No ATLAS automatic task registered.'
}
