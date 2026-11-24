$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
$source = Join-Path $PSScriptRoot 'payload'
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Open PowerShell at Laravel project root (artisan missing).' }
if (-not (Test-Path (Join-Path $source 'app\Http\Controllers\Admin\HnbChromePdfController.php'))) { throw 'Missing ZIP payload. Extract the complete package.' }
node --version
if ($LASTEXITCODE -ne 0) { throw 'Node.js is required' }
npm --version
if ($LASTEXITCODE -ne 0) { throw 'npm is required' }

# Install isolated Node dependency BEFORE modifying Laravel.
$npmFolder = Join-Path $root 'tools\pdf-chrome'
if (-not (Test-Path $npmFolder)) { New-Item -ItemType Directory -Force $npmFolder | Out-Null }
Copy-Item (Join-Path $source 'tools\pdf-chrome\package.json') (Join-Path $npmFolder 'package.json') -Force
Copy-Item (Join-Path $source 'tools\pdf-chrome\render.cjs') (Join-Path $npmFolder 'render.cjs') -Force
Push-Location $npmFolder
try {
  npm install --omit=dev --no-audit --no-fund
  if ($LASTEXITCODE -ne 0) { throw 'npm install failed. Laravel files not changed.' }
} finally { Pop-Location }

$web = Join-Path $root 'routes\web.php'
$routeInc = "require __DIR__.'/hnb_chrome_pdf.php';"
$oldWeb = [System.IO.File]::ReadAllText($web)
if ($oldWeb -notmatch 'hnb_chrome_pdf\.php') {
  # Check syntax before making a change
  $newWeb = $oldWeb + "`r`n// Isolated admin-only Chromium PDF preview`r`n" + $routeInc + "`r`n"
} else { $newWeb = $oldWeb }

$destList = @(
 'app\Http\Controllers\Admin\HnbChromePdfController.php',
 'resources\views\pdf\chrome\document.blade.php',
 'resources\views\pdf\chrome\style.blade.php',
 'routes\hnb_chrome_pdf.php'
)
$backup = Join-Path $root ('backup-chromium-pdf-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Force -Path $backup | Out-Null
Copy-Item $web (Join-Path $backup 'web.php')
$existing = @{}
foreach ($rel in $destList) {
 $dest = Join-Path $root $rel
 $existing[$rel] = Test-Path $dest
 if ($existing[$rel]) {
  $bp = Join-Path $backup $rel
  New-Item -ItemType Directory -Force (Split-Path $bp) | Out-Null
  Copy-Item $dest $bp -Force
 }
}
try {
 foreach ($rel in $destList) {
  $dest = Join-Path $root $rel
  New-Item -ItemType Directory -Force (Split-Path $dest) | Out-Null
  Copy-Item (Join-Path $source $rel) $dest -Force
 }
 [System.IO.File]::WriteAllText($web, $newWeb, [System.Text.UTF8Encoding]::new($false))
 php -l (Join-Path $root 'app\Http\Controllers\Admin\HnbChromePdfController.php')
 if ($LASTEXITCODE -ne 0) { throw 'Controller syntax failed' }
 php -l (Join-Path $root 'routes\hnb_chrome_pdf.php')
 if ($LASTEXITCODE -ne 0) { throw 'Routes syntax failed' }
 php artisan route:clear
 if ($LASTEXITCODE -ne 0) { throw 'route:clear failed' }
 php artisan view:clear
 if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
 php artisan view:cache
 if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
 php artisan route:list --name=admin.pdf-studio.show
 if ($LASTEXITCODE -ne 0) { throw 'PDF route is missing' }
 Write-Host "`nINSTALLED: HTML/CSS + Edge/Chrome PDF Studio (Admin only)." -ForegroundColor Green
 Write-Host "Backup: $backup"
 Write-Host 'PREVIEW: http://127.0.0.1:8000/admin/pdf-studio/quote/1'
 Write-Host 'DOWNLOAD: http://127.0.0.1:8000/admin/pdf-studio/quote/1?download=1'
 Write-Host 'Replace /quote/1 with real IDs (invoice, delivery, receipt).'
 Write-Host 'Existing PDFs, approvals, stock and database have NOT been changed.'
} catch {
 Copy-Item (Join-Path $backup 'web.php') $web -Force
 foreach ($rel in $destList) {
  $dest = Join-Path $root $rel
  if ($existing[$rel]) { Copy-Item (Join-Path $backup $rel) $dest -Force }
  else { Remove-Item $dest -Force -ErrorAction SilentlyContinue }
 }
 php artisan optimize:clear | Out-Null
 throw "INSTALL ROLLED BACK: $($_.Exception.Message)"
}
