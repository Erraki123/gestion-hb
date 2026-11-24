$ErrorActionPreference = 'Stop'
$root = (Get-Location).Path
if (-not (Test-Path (Join-Path $root 'artisan'))) { throw 'Lancez depuis le dossier racine de Laravel (artisan introuvable).' }
$source = Join-Path $root 'HNB_PDF_GOLD_REFINED_V10\payload'
$rels = @('resources\views\pdf\_style.blade.php', 'public\images\hnb-v7-zakhrafa-frame.png')
foreach ($rel in $rels) { if (-not (Test-Path (Join-Path $source $rel))) { throw "Fichier manquant dans ZIP: $rel" } }
$backup = Join-Path $root ('backup-pdf-v10-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -Path $backup -ItemType Directory -Force | Out-Null
$existing = @{}
foreach ($rel in $rels) {
  $dst = Join-Path $root $rel
  $existing[$rel] = Test-Path $dst
  if ($existing[$rel]) {
     $b = Join-Path $backup $rel
     New-Item -ItemType Directory -Path (Split-Path $b) -Force | Out-Null
     Copy-Item -LiteralPath $dst -Destination $b -Force
  }
}
try {
   foreach ($rel in $rels) {
      $dst = Join-Path $root $rel
      New-Item -ItemType Directory -Path (Split-Path $dst) -Force | Out-Null
      Copy-Item -LiteralPath (Join-Path $source $rel) -Destination $dst -Force
   }
   & php -l (Join-Path $root $rels[0]); if ($LASTEXITCODE -ne 0) { throw 'Syntax error' }
   & php artisan view:clear; if ($LASTEXITCODE -ne 0) { throw 'view:clear failed' }
   & php artisan view:cache; if ($LASTEXITCODE -ne 0) { throw 'view:cache failed' }
} catch {
   foreach ($rel in $rels) {
      $dst = Join-Path $root $rel
      if ($existing[$rel]) { Copy-Item -LiteralPath (Join-Path $backup $rel) -Destination $dst -Force }
      elseif (Test-Path $dst) { Remove-Item -LiteralPath $dst -Force }
   }
   & php artisan view:clear | Out-Null
   throw ('Installation annulee. Anciens fichiers restaures. ' + $_.Exception.Message)
}
Write-Host 'SUCCESS: HN BLOOM V10 installed (color + ornaments only).' -ForegroundColor Green
Write-Host ('Backup: ' + $backup)
Write-Host 'Logo size, header, footer, document fields and workflows unchanged.'
