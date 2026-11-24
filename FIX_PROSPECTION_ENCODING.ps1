$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Ouvrez PowerShell dans le dossier Laravel (artisan introuvable).' }
$target = Join-Path (Get-Location).Path 'resources\views\prospects\index.blade.php'
if (-not [System.IO.File]::Exists($target)) { throw "Fichier introuvable: $target" }
$utf8 = New-Object System.Text.UTF8Encoding($false, $true)
$original = [System.IO.File]::ReadAllText($target, $utf8)
$backup = Join-Path (Get-Location).Path ('backup-prospection-encoding-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -ItemType Directory -Force -Path $backup | Out-Null
Copy-Item $target (Join-Path $backup 'index.blade.php') -Force
# Fix only recognized accidental UTF-8-as-Windows-1252 sequences, not the entire template.
$fixes = [ordered]@{
  'Ã€'='À'; 'Ã‰'='É'; 'Ãˆ'='È'; 'ÃŠ'='Ê'; 'Ã‡'='Ç';
  'Ã '='à'; 'Ã¢'='â'; 'Ã§'='ç'; 'Ã©'='é'; 'Ã¨'='è'; 'Ãª'='ê'; 'Ã«'='ë';
  'Ã®'='î'; 'Ã¯'='ï'; 'Ã´'='ô'; 'Ã¹'='ù'; 'Ã»'='û'; 'Ã¼'='ü';
  'Â·'='·'; 'Â©'='©'; 'Â«'='«'; 'Â»'='»'; 'Â '=' ';
  'âœ•'='✕'; 'âœ“'='✓'; 'â€¢'='•'; 'â€¦'='…'; 'â€“'='–'; 'â€”'='—';
  'â€™'='’'; 'â€œ'='“'; 'â€'='”'; 'â‚¬'='€'
}
$updated = $original
foreach ($bad in $fixes.Keys) { $updated = $updated.Replace([string]$bad, [string]$fixes[$bad]) }
if ($updated -ne $original) {
  [System.IO.File]::WriteAllText($target, $updated, $utf8)
  Write-Host 'Encodage du template Prospection corrige.' -ForegroundColor Green
} else {
  Write-Host 'Aucune sequence incorrecte detectee dans ce template. Verifiez aussi les autres vues / reponse HTTP.' -ForegroundColor Yellow
}
php artisan view:clear
if ($LASTEXITCODE -ne 0) { throw 'view:clear a echoue' }
php artisan view:cache
if ($LASTEXITCODE -ne 0) { throw 'view:cache a echoue' }
Write-Host "Backup: $backup" -ForegroundColor Cyan
Write-Host 'Actualisez la page Prospection avec Ctrl + F5.' -ForegroundColor Green
