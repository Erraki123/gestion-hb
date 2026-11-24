$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Open PowerShell in the Laravel project root.' }
$src = Join-Path $root 'PATCH_SPRINT_B'
$paths = @(
 'app/Services/DocumentSender.php',
 'app/Http/Controllers/DocumentSendController.php',
 'app/Console/Commands/SendDocument.php',
 'config/sales_safety.php'
)
$backup = Join-Path $root ('backup-final-sprint-b-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
foreach ($rel in $paths) {
  $target = Join-Path $root $rel
  $patch = Join-Path $src $rel
  if (-not (Test-Path $patch)) { throw ('Missing patch: ' + $rel) }
  if (Test-Path $target) {
    $dest = Join-Path $backup $rel
    New-Item -ItemType Directory -Path (Split-Path $dest) -Force | Out-Null
    Copy-Item -LiteralPath $target -Destination $dest -Force
  }
}
$copied = @()
try {
  foreach ($rel in $paths) {
    $patch = Join-Path $src $rel
    $target = Join-Path $root $rel
    New-Item -ItemType Directory -Path (Split-Path $target) -Force | Out-Null
    Copy-Item -LiteralPath $patch -Destination $target -Force
    $copied += $rel
  }
  foreach ($rel in $paths) {
    $target = Join-Path $root $rel
    & php -l $target
    if ($LASTEXITCODE -ne 0) { throw ('PHP lint failed: ' + $rel) }
  }
  & php artisan config:clear
  if ($LASTEXITCODE -ne 0) { throw 'config:clear failed' }
  & php artisan route:list --name=documents.send
  if ($LASTEXITCODE -ne 0) { throw 'route:list failed' }
  Write-Host ('SPRINT B SAFETY INSTALLED. Backup: ' + $backup) -ForegroundColor Green
  Write-Host 'No migrations, no automatic messages, no database writes.'
} catch {
  foreach ($rel in $copied) {
    $target = Join-Path $root $rel
    $old = Join-Path $backup $rel
    if (Test-Path $old) { Copy-Item -LiteralPath $old -Destination $target -Force }
    elseif (Test-Path $target) { Remove-Item -LiteralPath $target -Force }
  }
  throw
}
