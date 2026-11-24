$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) {
  throw 'Open PowerShell in the Laravel project folder (artisan not found).'
}
$src = Join-Path $PSScriptRoot 'payload'
$files = @(
 'resources\views\pdf\_header.blade.php',
 'resources\views\pdf\_footer.blade.php',
 'resources\views\pdf\_style.blade.php',
 'public\images\hnbloom-premium-gold.png',
 'public\images\hnbloom-gold-corner-top.png',
 'public\images\hnbloom-gold-corner-bottom.png'
)
foreach ($rel in $files) {
  if (-not (Test-Path (Join-Path $src $rel))) { throw "Missing package file: $rel" }
}
$backup = Join-Path $root ('backup-hnb-gold-v5-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
$written = @()
try {
  foreach ($rel in $files) {
    $target = Join-Path $root $rel
    $source = Join-Path $src $rel
    New-Item -ItemType Directory -Path (Split-Path $target -Parent) -Force | Out-Null
    if (Test-Path $target) {
      $bak = Join-Path $backup $rel
      New-Item -ItemType Directory -Path (Split-Path $bak -Parent) -Force | Out-Null
      Copy-Item -LiteralPath $target -Destination $bak -Force
    }
    Copy-Item -LiteralPath $source -Destination $target -Force
    $written += $rel
  }
  foreach ($rel in @('resources\views\pdf\_header.blade.php', 'resources\views\pdf\_footer.blade.php', 'resources\views\pdf\_style.blade.php')) {
    & php -l (Join-Path $root $rel)
    if ($LASTEXITCODE -ne 0) { throw "PHP syntax check failed: $rel" }
  }
  & php artisan view:clear
  if ($LASTEXITCODE -ne 0) { throw 'Laravel view:clear failed' }
  & php artisan view:cache
  if ($LASTEXITCODE -ne 0) { throw 'Laravel view:cache failed' }
} catch {
  foreach ($rel in $written) {
    $target = Join-Path $root $rel
    $bak = Join-Path $backup $rel
    if (Test-Path $bak) { Copy-Item -LiteralPath $bak -Destination $target -Force }
    elseif (Test-Path $target) { Remove-Item -LiteralPath $target -Force }
  }
  Write-Host 'Install failed. Changed files restored.' -ForegroundColor Red
  throw
}
Write-Host 'HNB PDF SIGNATURE GOLD V5 INSTALLED' -ForegroundColor Green
Write-Host ('Backup: ' + $backup)
Write-Host 'Only PDF design files updated. No DB, routes, approvals or sending changed.'
