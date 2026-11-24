$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Ouvre PowerShell dans le dossier Laravel contenant artisan.' }
$style = Join-Path $root 'resources\views\pdf\_style.blade.php'
$header = Join-Path $root 'resources\views\pdf\_header.blade.php'
if (-not ((Test-Path $style) -and (Test-Path $header))) { throw 'Les fichiers PDF ne sont pas presents.' }
$css = [System.IO.File]::ReadAllText($style)
$hdr = [System.IO.File]::ReadAllText($header)
if ($css.Contains('HNB_PDF_FIX_BLANK_PAGES_V11')) {
  Write-Host 'V11 already installed.' -ForegroundColor Yellow
  exit 0
}
if (-not $css.Contains('hnb-v7-page-ornament') -or -not $hdr.Contains('hnb-v7-page-ornament')) {
  throw 'V7/V10 ornament markers missing. Nothing modified; send the current _header and _style for a targeted fix.'
}
if (-not $css.Contains('</style>')) { throw 'CSS style closing tag absent. Nothing modified.' }
$backup = Join-Path $root ('backup-pdf-pagination-v11-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Path $backup -Force | Out-Null
Copy-Item -LiteralPath $style -Destination (Join-Path $backup '_style.blade.php') -Force
Copy-Item -LiteralPath $header -Destination (Join-Path $backup '_header.blade.php') -Force
$fix = @'
/* HNB_PDF_FIX_BLANK_PAGES_V11: avoid a full-A4 fixed image creating overflow pages */
.hnb-v7-page-ornament, .hnb-v7-page-ornament img { display:none !important; }
.hnb-v6-bottom-deco, .hnb-v6-bottom-seal, .hnb-v6-top-medallion,
.hnb-gold-art-top, .hnb-gold-art-bottom { display:none !important; }
.watermark, .watermark img { display:none !important; }
body { margin:0 !important; padding:0 !important; }
/* Lightweight gold motif without an A4-sized decoration element */
.head-shell { border-left:1px solid #e4cf9a; border-right:1px solid #e4cf9a; }
.head-shell:before { content:""; position:absolute; top:4px; left:4px; width:28px; height:28px; border-left:1px solid #D4A332; border-top:1px solid #D4A332; }
.head-shell:after { content:""; position:absolute; top:4px; right:4px; width:28px; height:28px; border-right:1px solid #D4A332; border-top:1px solid #D4A332; }
.footer { background:#fffdfa !important; }
'@
try {
  $newCss = $css.Replace('</style>', "$fix`r`n</style>")
  [System.IO.File]::WriteAllText($style, $newCss, [System.Text.UTF8Encoding]::new($false))
  # Delete only the V7 full-page decorative element, not the logo or content
  $pattern = '(?s)\{\{-- Printed page filigree: decorative asset, no business data changed --\}\}\s*@if\s*\(file_exists\(public_path\(''images/hnb-v7-zakhrafa-frame\.png''\)\)\)\s*<div class="hnb-v7-page-ornament">.*?</div>\s*@endif'
  # Do not require the exact comment formatting; CSS hide works regardless
  & php -l $style
  if ($LASTEXITCODE -ne 0) { throw 'PHP syntax failed.' }
  & php artisan view:clear
  if ($LASTEXITCODE -ne 0) { throw 'view:clear failed.' }
  & php artisan view:cache
  if ($LASTEXITCODE -ne 0) { throw 'view:cache failed.' }
} catch {
  Copy-Item -LiteralPath (Join-Path $backup '_style.blade.php') -Destination $style -Force
  Copy-Item -LiteralPath (Join-Path $backup '_header.blade.php') -Destination $header -Force
  & php artisan view:clear | Out-Null
  throw ('Fix failed. Backup restored. ' + $_.Exception.Message)
}
Write-Host 'SUCCESS: V11 page overflow protection installed.' -ForegroundColor Green
Write-Host ('BACKUP: ' + $backup)
Write-Host 'Re-generate a NEW PDF; do not open the old download.'
Write-Host 'No controllers, data, workflow or payments changed.'
