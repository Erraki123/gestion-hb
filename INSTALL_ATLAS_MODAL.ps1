$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Run in Laravel project root.' }
if (-not (Test-Path '.\PATCH_ATLAS\modal.blade.fragment')) { throw 'Missing patch files. Extract ZIP to project root.' }
php -l '.\APPLY_ATLAS_SHEET.php'
if ($LASTEXITCODE -ne 0) { throw 'PHP patch syntax failed.' }
php '.\APPLY_ATLAS_SHEET.php'
if ($LASTEXITCODE -ne 0) { throw 'Patch not applied. Existing view was preserved.' }
php artisan view:clear
if ($LASTEXITCODE -ne 0) { throw 'Blade cache clear failed.' }
php artisan view:cache
if ($LASTEXITCODE -ne 0) {
  $backup = Get-Content '.\ATLAS_MODAL_BACKUP_PATH.txt' -Raw
  $backup = $backup.Trim()
  if ($backup -and (Test-Path (Join-Path $backup '_discovery_review.blade.php'))) {
    Copy-Item (Join-Path $backup '_discovery_review.blade.php') '.\resources\views\prospects\_discovery_review.blade.php' -Force
    php artisan view:clear
  }
  throw 'Blade compilation failed; previous view restored if backed up.'
}
Write-Host 'ATLAS modal installed. No DB updates, no messages sent.' -ForegroundColor Green
