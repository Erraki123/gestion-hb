$ErrorActionPreference = 'Stop'
$project = (Get-Location).Path
$pack = Split-Path -Parent $MyInvocation.MyCommand.Path
if (-not (Test-Path (Join-Path $project 'artisan'))) { throw 'Lancez depuis le dossier racine Laravel.' }
$files = @(
  'app/Http/Controllers/Admin/HnbChromePdfController.php',
  'routes/hnb_chrome_pdf.php',
  'resources/views/pdf/chrome/document.blade.php',
  'resources/views/pdf/chrome/style.blade.php'
)
$expected = @{
  'app/Http/Controllers/Admin/HnbChromePdfController.php'='38b7b6d62e6d87049a73682e03761d4ecd665b6d6449d7c1f6b5f53ec8c7b337'
  'routes/hnb_chrome_pdf.php'='f51ff3378e5f61a99fff728c954543671aeff4486e089442115b937ffee26a6e'
}
foreach ($rel in $files) {
  $dest = Join-Path $project $rel
  $src = Join-Path $pack ('payload/' + $rel)
  if (-not (Test-Path $dest) -or -not (Test-Path $src)) { throw "Missing file: $rel. No changes made." }
  if ($expected.ContainsKey($rel)) {
     $actual = (Get-FileHash $dest -Algorithm SHA256).Hash.ToLower()
     if ($actual -ne $expected[$rel]) { throw "Current $rel differs from audited version. No changes made. Send updated file." }
  }
}
$backup = Join-Path $project ('backup-hnb-chrome-admin-commercial-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
foreach ($rel in $files) {
  $src = Join-Path $project $rel
  $dst = Join-Path $backup $rel
  New-Item -ItemType Directory -Force -Path (Split-Path $dst -Parent) | Out-Null
  Copy-Item $src $dst -Force
}
try {
  foreach ($rel in $files) {
    Copy-Item (Join-Path $pack ('payload/' + $rel)) (Join-Path $project $rel) -Force
  }
  php -l 'app/Http/Controllers/Admin/HnbChromePdfController.php'
  if ($LASTEXITCODE -ne 0) { throw 'Controller syntax' }
  php -l 'routes/hnb_chrome_pdf.php'
  if ($LASTEXITCODE -ne 0) { throw 'Route syntax' }
  php artisan optimize:clear
  if ($LASTEXITCODE -ne 0) { throw 'optimize clear failed' }
  php artisan view:cache
  if ($LASTEXITCODE -ne 0) { throw 'view cache failed' }
  php artisan route:list --name=admin.pdf-studio.show
  if ($LASTEXITCODE -ne 0) { throw 'route list failed' }
  Write-Host 'SUCCESS: Original Chromium design restored for Admin and Commercial.' -ForegroundColor Green
  Write-Host 'Commercial can preview own documents. PDF download protected by director approvals.'
  Write-Host "BACKUP: $backup"
} catch {
  foreach ($rel in $files) { Copy-Item (Join-Path $backup $rel) (Join-Path $project $rel) -Force }
  php artisan optimize:clear | Out-Null
  Write-Host "RESTORED BACKUP: $backup" -ForegroundColor Yellow
  throw
}
