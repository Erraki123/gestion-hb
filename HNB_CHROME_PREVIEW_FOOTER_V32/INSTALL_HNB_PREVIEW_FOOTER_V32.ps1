$ErrorActionPreference = 'Stop'
$project = (Get-Location).Path
$target = Join-Path $project 'resources\views\pdf\chrome\style.blade.php'
$marker = 'HNB_PREVIEW_A4_FOOTER_FIX_V32'
if (-not (Test-Path (Join-Path $project 'artisan')) -or -not (Test-Path $target)) {
    throw 'Run from the Laravel gestion-commerciale project root.'
}
$original = [System.IO.File]::ReadAllText($target, [System.Text.Encoding]::UTF8)
if ($original.Contains($marker)) {
    Write-Host 'V32 already installed. No changes.' -ForegroundColor Yellow
    exit 0
}
if (-not [regex]::IsMatch($original, '</style>\s*$', 'IgnoreCase')) {
    throw 'CSS Blade closing </style> missing. Nothing changed.'
}
$backup = Join-Path $project ('backup-hnb-preview-footer-v32-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Force -Path $backup | Out-Null
Copy-Item $target (Join-Path $backup 'style.blade.php') -Force
$css = @'
/* HNB_PREVIEW_A4_FOOTER_FIX_V32
 * SCREEN HTML PREVIEW ONLY. Chromium PDF V31 print remains unchanged.
 * Keep footer AFTER the content, at bottom of short A4 pages.
 * Long previews grow normally instead of covering the content.
 */
@media screen {
  html { background:#e9ecef !important; }
  body {
    position:relative !important;
    display:flex !important;
    flex-direction:column !important;
    width:210mm !important;
    min-height:297mm !important;
    padding:14mm !important;
    margin:20px auto !important;
    background:#fff !important;
    box-shadow:0 8px 30px rgba(0,0,0,.13) !important;
  }
  .sheet {
    position:static !important;
    flex:1 0 auto !important;
    min-height:0 !important;
    width:100% !important;
    max-width:none !important;
    padding:0 !important;
    margin:0 !important;
    box-shadow:none !important;
    background:transparent !important;
  }
  footer.page-footer {
    position:static !important;
    flex:0 0 auto !important;
    display:block !important;
    top:auto !important;
    bottom:auto !important;
    left:auto !important;
    right:auto !important;
    width:100% !important;
    max-width:none !important;
    min-height:0 !important;
    margin:0 !important;
    padding:4mm 0 0 !important;
    background:white !important;
  }
  footer.page-footer .footer-sweep { display:none !important; }
}
'@
$revised = [regex]::Replace($original, '(?i)</style>\s*$', "`r`n$css`r`n</style>`r`n")
try {
    [System.IO.File]::WriteAllText($target, $revised, (New-Object System.Text.UTF8Encoding($false)))
    php -l $target
    if ($LASTEXITCODE -ne 0) { throw 'PHP lint failed' }
    php artisan view:clear
    if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
    php artisan view:cache
    if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
    Write-Host 'SUCCESS V32: HTML preview footer fixed. PDF print renderer unchanged.' -ForegroundColor Green
    Write-Host ('BACKUP: ' + $backup)
} catch {
    Copy-Item (Join-Path $backup 'style.blade.php') $target -Force
    Write-Host 'Installation failed. Original CSS restored.' -ForegroundColor Red
    throw
}
