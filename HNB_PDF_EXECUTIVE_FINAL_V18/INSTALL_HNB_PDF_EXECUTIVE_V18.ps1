$ErrorActionPreference = 'Stop'
$project = (Get-Location).Path
if (-not (Test-Path (Join-Path $project 'artisan'))) { throw 'Run from Laravel project root (artisan missing).' }
$pack = Join-Path $PSScriptRoot 'payload'
$paths = @(
  'resources/views/pdf/_header.blade.php',
  'resources/views/pdf/_footer.blade.php',
  'resources/views/pdf/_style.blade.php',
  'resources/views/invoices/pdf.blade.php'
)
$icons = Get-ChildItem -Path (Join-Path $pack 'public/images/hnb-pdf-icons') -Filter '*.png' | ForEach-Object { 'public/images/hnb-pdf-icons/' + $_.Name }
$paths += $icons
$paths += 'public/images/hnb-pdf-v18/signature-lines.png'
foreach ($rel in $paths) {
  if (-not (Test-Path (Join-Path $pack $rel))) { throw "Missing source: $rel" }
}
$backup = Join-Path $project ('backup-hnb-pdf-v18-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backup | Out-Null
$written = @()
try {
  foreach ($rel in $paths) {
    $dest = Join-Path $project $rel
    $src = Join-Path $pack $rel
    if (Test-Path $dest) {
      $destBackup = Join-Path $backup $rel
      New-Item -ItemType Directory -Force -Path (Split-Path $destBackup -Parent) | Out-Null
      Copy-Item $dest $destBackup -Force
    }
    New-Item -ItemType Directory -Force -Path (Split-Path $dest -Parent) | Out-Null
    Copy-Item $src $dest -Force
    $written += $rel
  }
  foreach ($rel in @('resources/views/pdf/_header.blade.php','resources/views/pdf/_footer.blade.php','resources/views/pdf/_style.blade.php','resources/views/invoices/pdf.blade.php')) {
    & php -l (Join-Path $project $rel)
    if ($LASTEXITCODE -ne 0) { throw "PHP syntax failed: $rel" }
  }
  & php artisan view:clear
  if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
  & php artisan view:cache
  if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
  Write-Host 'SUCCESS: HN BLOOM Executive Signature V18 installed.' -ForegroundColor Green
  Write-Host "BACKUP: $backup"
  Write-Host 'No migrations, database changes, queue jobs or workflow edits.'
} catch {
  foreach ($rel in $written) {
    $dest = Join-Path $project $rel
    $prev = Join-Path $backup $rel
    if (Test-Path $prev) { Copy-Item $prev $dest -Force }
    elseif (Test-Path $dest) { Remove-Item $dest -Force }
  }
  & php artisan view:clear | Out-Null
  Write-Host 'Original files restored because install failed.' -ForegroundColor Yellow
  throw
}
