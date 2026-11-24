$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Run from Laravel project root.' }
if (-not (Test-Path '.\storage\app\prospect-imports\atlas-contact-review.csv')) { throw 'Contact review CSV missing. Run Phase 6M first.' }
php -l '.\APPLY_ATLAS_PHASE6N.php'
if ($LASTEXITCODE -ne 0) { throw 'Patch syntax error.' }
php -l '.\PATCH_ATLAS_6N\app\Services\AtlasContactReviewService.php'
if ($LASTEXITCODE -ne 0) { throw 'Service syntax error.' }
php '.\APPLY_ATLAS_PHASE6N.php'
if ($LASTEXITCODE -ne 0) { throw 'Patch failed; no changes applied.' }
php artisan view:clear
if ($LASTEXITCODE -ne 0) { throw 'View cache clear failed.' }
php artisan view:cache
if ($LASTEXITCODE -ne 0) {
    if (Test-Path '.\ATLAS_6N_BACKUP_PATH.txt') {
        $backup = (Get-Content '.\ATLAS_6N_BACKUP_PATH.txt' -Raw).Trim()
        if (Test-Path (Join-Path $backup '_discovery_review.blade.php')) {
            Copy-Item (Join-Path $backup '_discovery_review.blade.php') '.\resources\views\prospects\_discovery_review.blade.php' -Force
            if (Test-Path (Join-Path $backup 'AtlasContactReviewService.php')) {
                Copy-Item (Join-Path $backup 'AtlasContactReviewService.php') '.\app\Services\AtlasContactReviewService.php' -Force
            } else { Remove-Item '.\app\Services\AtlasContactReviewService.php' -ErrorAction SilentlyContinue }
            php artisan view:clear
        }
    }
    throw 'Blade compilation failed; files restored.'
}
Write-Host 'ATLAS Phase 6N installed. Read-only contact suggestions. No DB writes, no outbound messages.' -ForegroundColor Green
