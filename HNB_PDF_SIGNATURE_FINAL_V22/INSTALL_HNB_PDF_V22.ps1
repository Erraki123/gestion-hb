$ErrorActionPreference = 'Stop'
$base = Split-Path -Parent $MyInvocation.MyCommand.Path
if (!(Test-Path '.\artisan')) { throw 'Lance le script depuis la racine du projet Laravel.' }
$project = (Get-Location).Path
$files = @('resources\views\pdf\_header.blade.php','resources\views\pdf\_footer.blade.php','resources\views\pdf\_style.blade.php')
foreach ($relative in $files) {
  if (!(Test-Path (Join-Path $project $relative))) { throw "Fichier manquant: $relative" }
  if (!(Test-Path (Join-Path (Join-Path $base 'payload') $relative))) { throw "Archive incomplete: $relative" }
}
$backup = Join-Path $project ('backup-hnb-pdf-v22-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
foreach ($relative in $files) { Copy-Item (Join-Path $project $relative) (Join-Path $backup (Split-Path $relative -Leaf)) -Force }
$imagesDir = Join-Path $project 'public\images\hnb-pdf-v22'
$imagesExisted = Test-Path $imagesDir
try {
  New-Item -ItemType Directory -Path $imagesDir -Force | Out-Null
  Copy-Item (Join-Path $base 'payload\public\images\hnb-pdf-v22\*') -Destination $imagesDir -Force
  foreach ($relative in $files) {
    $target = Join-Path $project $relative
    Copy-Item (Join-Path (Join-Path $base 'payload') $relative) $target -Force
    & php -l $target
    if ($LASTEXITCODE -ne 0) { throw "PHP syntax failure: $relative" }
  }
  & php artisan view:clear
  if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
  & php artisan view:cache
  if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
  Write-Host 'SUCCESS: HN BLOOM Signature V22 installed.' -ForegroundColor Green
  Write-Host "Backup: $backup"
  Write-Host 'Design templates only: no database, controller, workflow or auto-send changes.'
} catch {
  foreach ($relative in $files) {
    Copy-Item (Join-Path $backup (Split-Path $relative -Leaf)) (Join-Path $project $relative) -Force
  }
  if (!$imagesExisted -and (Test-Path $imagesDir)) { Remove-Item $imagesDir -Recurse -Force }
  & php artisan view:clear | Out-Null
  Write-Host "ROLLED BACK: $backup" -ForegroundColor Yellow
  throw
}
