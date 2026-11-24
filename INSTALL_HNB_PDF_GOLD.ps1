$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Open PowerShell inside the Laravel project root (artisan not found).' }
$src = Join-Path $PSScriptRoot 'payload'
$parts = @(
  'resources\views\pdf\_header.blade.php',
  'resources\views\pdf\_footer.blade.php',
  'resources\views\pdf\_style.blade.php',
  'public\images\hnbloom-premium-gold.png'
)
foreach ($rel in $parts) { if (-not (Test-Path (Join-Path $src $rel))) { throw "Package incomplete: $rel" } }
$backup = Join-Path $root ('backup-hnb-pdf-gold-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
$written = @()
try {
  foreach ($rel in $parts) {
    $target = Join-Path $root $rel
    $source = Join-Path $src $rel
    $destinationDir = Split-Path $target -Parent
    New-Item -ItemType Directory -Path $destinationDir -Force | Out-Null
    if (Test-Path $target) {
       $bak = Join-Path $backup $rel
       New-Item -ItemType Directory -Path (Split-Path $bak -Parent) -Force | Out-Null
       Copy-Item -LiteralPath $target -Destination $bak -Force
    }
    Copy-Item -LiteralPath $source -Destination $target -Force
    $written += $rel
  }
  foreach ($rel in @('resources\views\pdf\_header.blade.php','resources\views\pdf\_footer.blade.php','resources\views\pdf\_style.blade.php')) {
    & php -l (Join-Path $root $rel)
    if ($LASTEXITCODE -ne 0) { throw "PHP syntax failed: $rel" }
  }
  & php artisan view:clear
  if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
  & php artisan view:cache
  if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
} catch {
  foreach ($rel in $written) {
     $target = Join-Path $root $rel
     $bak = Join-Path $backup $rel
     if (Test-Path $bak) { Copy-Item -LiteralPath $bak -Destination $target -Force }
     elseif (Test-Path $target) { Remove-Item -LiteralPath $target -Force }
  }
  Write-Host 'Installation failed; files restored from backup.' -ForegroundColor Red
  throw
}
Write-Host 'HN BLOOM PREMIUM GOLD PDF INSTALLED' -ForegroundColor Green
Write-Host ('Backup: ' + $backup)
Write-Host 'Changed only PDF layout assets. No CRM database, workflows, approvals, or PDF data fields changed.'
