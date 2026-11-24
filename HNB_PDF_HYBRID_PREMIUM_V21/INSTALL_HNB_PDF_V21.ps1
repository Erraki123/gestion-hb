$ErrorActionPreference = 'Stop'
$base = Split-Path -Parent $MyInvocation.MyCommand.Path
$project = Get-Location
if (!(Test-Path '.\artisan')) { throw 'Lance ce script a la racine Laravel (artisan introuvable).' }
$files = @('resources\views\pdf\_header.blade.php','resources\views\pdf\_footer.blade.php','resources\views\pdf\_style.blade.php')
$backup = Join-Path $project ('backup-hnb-pdf-v21-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item $backup -ItemType Directory -Force | Out-Null
$old = @{}
foreach ($rel in $files) {
  $dest=Join-Path $project $rel
  if (!(Test-Path $dest)) { throw "Missing: $rel" }
  $old[$rel]=[System.IO.File]::ReadAllBytes($dest)
  Copy-Item $dest (Join-Path $backup (Split-Path $rel -Leaf)) -Force
}
$targetImages = Join-Path $project 'public\images\hnb-pdf-v21'
$imagesExist = Test-Path $targetImages
try {
  New-Item -ItemType Directory -Force $targetImages | Out-Null
  Copy-Item (Join-Path $base 'payload\public\images\hnb-pdf-v21\*') $targetImages -Force
  foreach ($rel in $files) {
    $src=Join-Path (Join-Path $base 'payload') $rel
    $dest=Join-Path $project $rel
    Copy-Item $src $dest -Force
    & php -l $dest
    if ($LASTEXITCODE -ne 0) { throw "PHP syntax failure: $rel" }
  }
  & php artisan view:clear
  if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
  & php artisan view:cache
  if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
  Write-Host 'SUCCESS: HN BLOOM V21 premium header, body and readable footer.' -ForegroundColor Green
  Write-Host "BACKUP: $backup"
  Write-Host 'Only three Blade presentation files and two images changed. No DB, no controllers, no approvals.'
} catch {
  foreach ($rel in $files) { [System.IO.File]::WriteAllBytes((Join-Path $project $rel),$old[$rel]) }
  if (-not $imagesExist -and (Test-Path $targetImages)) { Remove-Item -Recurse -Force $targetImages }
  & php artisan view:clear | Out-Null
  throw
}
