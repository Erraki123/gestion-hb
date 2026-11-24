$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
$source = Join-Path $root 'HNB_PDF_ZAKHRAFA_V7\payload'
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Executez depuis le dossier Laravel contenant artisan.' }
if (-not (Test-Path (Join-Path $source 'resources\views\pdf\_header.blade.php'))) { throw 'ZIP non extrait dans le dossier Laravel.' }
$rels = @(
 'resources\views\pdf\_header.blade.php',
 'resources\views\pdf\_footer.blade.php',
 'resources\views\pdf\_style.blade.php',
 'public\images\hnb-v7-zakhrafa-frame.png',
 'public\images\hnb-v7-zakhrafa-seal.png'
)
$backup = Join-Path $root ('backup-pdf-zakhrafa-v7-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -Path $backup -ItemType Directory -Force | Out-Null
$originals = @{}
foreach ($rel in $rels) {
 $dst = Join-Path $root $rel
 $originals[$rel] = Test-Path $dst
 if ($originals[$rel]) {
  $b = Join-Path $backup $rel
  New-Item -ItemType Directory -Path (Split-Path $b) -Force | Out-Null
  Copy-Item $dst $b -Force
 }
}
try {
 foreach ($rel in $rels) {
  $src = Join-Path $source $rel
  if (-not (Test-Path $src)) { throw "Missing payload: $rel" }
  $dst = Join-Path $root $rel
  New-Item -ItemType Directory -Path (Split-Path $dst) -Force | Out-Null
  Copy-Item $src $dst -Force
 }
 foreach ($rel in $rels[0..2]) {
  & php -l (Join-Path $root $rel)
  if ($LASTEXITCODE -ne 0) { throw "PHP syntax error $rel" }
 }
 & php artisan view:clear
 if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
 & php artisan view:cache
 if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
} catch {
 Write-Warning ('Install error: ' + $_.Exception.Message)
 foreach ($rel in $rels) {
  $dst = Join-Path $root $rel
  if ($originals[$rel]) { Copy-Item (Join-Path $backup $rel) $dst -Force }
  elseif (Test-Path $dst) { Remove-Item $dst -Force }
 }
 & php artisan view:clear | Out-Null
 throw 'Installation failed. Source restored.'
}
Write-Host ('SUCCESS: HN BLOOM ZAKHRAFA V7 installed. Backup: ' + $backup) -ForegroundColor Green
Write-Host 'Only PDF design files changed. No DB, controllers, invoices or approval workflows modified.'
