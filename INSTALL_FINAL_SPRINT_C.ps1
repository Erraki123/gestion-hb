$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest
$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Run this in the Laravel project root.' }
if (-not (Test-Path (Join-Path $root 'config/sales_safety.php'))) { throw 'Install Sprint B first.' }
$src = Join-Path $root 'PATCH_SPRINT_C'
$files = @(
 'app/Services/DocumentReleaseApproval.php',
 'app/Services/DocumentSender.php',
 'app/Http/Controllers/DocumentReleaseApprovalController.php',
 'app/Http/Controllers/DocumentSendController.php',
 'app/Jobs/SendDocumentJob.php',
 'database/migrations/2026_11_15_160000_create_document_release_approvals_table.php',
 'resources/views/documents/_release_review.blade.php',
 'routes/document_release_sprint_c.php'
)
$changed = @('routes/web.php','resources/views/quotes/show.blade.php','resources/views/invoices/show.blade.php','resources/views/quotes/_rows.blade.php','resources/views/invoices/_rows.blade.php')
$all = @($files + $changed)
$backup = Join-Path $root ('backup-final-sprint-c-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
foreach ($rel in $all) {
    $target = Join-Path $root $rel
    if (Test-Path $target) {
        $dest = Join-Path $backup $rel
        New-Item -ItemType Directory -Path (Split-Path $dest) -Force | Out-Null
        Copy-Item -LiteralPath $target -Destination $dest -Force
    }
}
$copied = @()
try {
    foreach ($rel in $files) {
        $from = Join-Path $src $rel
        if (-not (Test-Path $from)) { throw ('Missing patch: ' + $rel) }
        $dest = Join-Path $root $rel
        New-Item -ItemType Directory -Path (Split-Path $dest) -Force | Out-Null
        Copy-Item -LiteralPath $from -Destination $dest -Force
        $copied += $rel
    }
    $routeFile = Join-Path $root 'routes/web.php'
    $routeText = [System.IO.File]::ReadAllText($routeFile)
    $routeMarker = "require __DIR__ . '/document_release_sprint_c.php';"
    if (-not $routeText.Contains($routeMarker)) {
        [System.IO.File]::AppendAllText($routeFile, "`r`n" + $routeMarker + "`r`n", [System.Text.UTF8Encoding]::new($false))
    }
    foreach ($type in @('quote','invoice')) {
        $rel = if ($type -eq 'quote') { 'resources/views/quotes/show.blade.php' } else { 'resources/views/invoices/show.blade.php' }
        $path = Join-Path $root $rel
        $contents = [System.IO.File]::ReadAllText($path)
        $marker = "@include('documents._release_review'"
        if (-not $contents.Contains($marker)) {
            if ($type -eq 'quote') {
                $anchor = '<div class="quote-show py-3 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">'
                $inject = "`r`n        @include('documents._release_review', ['document' => `$quote, 'type' => 'quote'])"
            } else {
                $anchor = '<x-app-layout dark-header>'
                $inject = "`r`n    @include('documents._release_review', ['document' => `$invoice, 'type' => 'invoice'])"
            }
            if (-not $contents.Contains($anchor)) { throw ('Missing Blade anchor in ' + $rel) }
            # For invoice, add after existing header is complete (not immediately under layout).
            if ($type -eq 'invoice') {
                $anchor = '<div class="'
                # Instead inject in the first main content wrapper, whose placement is checked below.
                $pos = $contents.IndexOf('</x-slot>')
                if ($pos -lt 0) { throw 'Invoice header slot not found.' }
                $contents = $contents.Insert($pos + '</x-slot>'.Length, $inject)
            } else {
                $contents = $contents.Replace($anchor, $anchor + $inject)
            }
            [System.IO.File]::WriteAllText($path, $contents, [System.Text.UTF8Encoding]::new($false))
        }
    }
    # Hide misleading send buttons in list tables: detail page now shows release status/actions.
    foreach ($rel in @('resources/views/quotes/_rows.blade.php','resources/views/invoices/_rows.blade.php')) {
        $path = Join-Path $root $rel
        $contents = [System.IO.File]::ReadAllText($path)
        # Keep existing buttons for approved Admin documents only.
        $formSearch = '<form method="POST" action="{{ route(''documents.send'', '
        if ($contents.Contains($formSearch) -and -not $contents.Contains('DocumentReleaseApproval::class)->canRelease(')) {
            $needle = $formSearch
            $typeArg = if ($rel.Contains('quotes')) { '$q' } else { '$inv' }
            $guard = '@if (auth()->user()->isAdmin() && app(\App\Services\DocumentReleaseApproval::class)->canRelease(' + $typeArg + '))'
            $start = $contents.IndexOf($needle)
            $end = $contents.IndexOf('</form>', $start)
            if ($end -lt 0) { throw ('Send form end missing: ' + $rel) }
            $end += '</form>'.Length
            $contents = $contents.Substring(0,$start) + $guard + "`r`n" + $contents.Substring($start,$end-$start) + "`r`n@endif" + $contents.Substring($end)
            [System.IO.File]::WriteAllText($path,$contents,[System.Text.UTF8Encoding]::new($false))
        }
    }
    foreach ($rel in $files) {
        if ($rel.EndsWith('.php')) {
            & php -l (Join-Path $root $rel)
            if ($LASTEXITCODE -ne 0) { throw ('PHP syntax error: ' + $rel) }
        }
    }
    & php -l $routeFile
    if ($LASTEXITCODE -ne 0) { throw 'web routes syntax error' }
    & php artisan route:clear
    if ($LASTEXITCODE -ne 0) { throw 'route clear failed' }
    & php artisan route:list --name=documents.release
    if ($LASTEXITCODE -ne 0) { throw 'release route check failed' }
    & php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'view cache failed' }
    Write-Host 'Source and views validated. Applying release-approval migration...' -ForegroundColor Cyan
    & php artisan migrate --force --path=database/migrations/2026_11_15_160000_create_document_release_approvals_table.php
    if ($LASTEXITCODE -ne 0) { throw 'Migration failed' }
    Write-Host ('SPRINT C INSTALLED. Backup source files: ' + $backup) -ForegroundColor Green
    Write-Host 'Director approval mandatory for every outgoing quote/invoice, including queued jobs.'
} catch {
    foreach ($rel in $all) {
        $target = Join-Path $root $rel
        $old = Join-Path $backup $rel
        if (Test-Path $old) { Copy-Item -LiteralPath $old -Destination $target -Force }
        elseif ($copied -contains $rel -and (Test-Path $target)) { Remove-Item -LiteralPath $target -Force }
    }
    Write-Warning ('Source restored from backup ' + $backup + '. Check database migration status separately.')
    throw
}
