$ErrorActionPreference = 'Stop'
$base = Split-Path -Parent $MyInvocation.MyCommand.Path
$project = Get-Location
$viewDir = Join-Path $project 'resources\views\pdf'
$assetDir = Join-Path $project 'public\images\hnb-pdf-hybrid-v20'
$payload = Join-Path $base 'payload'

if (!(Test-Path (Join-Path $project 'artisan')) -or !(Test-Path (Join-Path $viewDir '_style.blade.php'))) {
  throw 'Exécute depuis la racine Laravel (artisan et resources/views/pdf requis).'
}
$sourceStyle = Join-Path $viewDir '_style.blade.php'
$sourceHeader = Join-Path $viewDir '_header.blade.php'
$sourceFooter = Join-Path $viewDir '_footer.blade.php'
$allOld = @{}
foreach ($f in @($sourceStyle,$sourceHeader,$sourceFooter)) {
  if (!(Test-Path $f)) { throw "Fichier introuvable: $f" }
  $allOld[$f] = [System.IO.File]::ReadAllBytes($f)
}
$backup = Join-Path $project ('backup-hnb-pdf-v20-' + (Get-Date -Format 'yyyyMMdd-HHmmss'))
New-Item -Path $backup -ItemType Directory -Force | Out-Null
foreach ($f in @($sourceStyle,$sourceHeader,$sourceFooter)) { Copy-Item $f (Join-Path $backup (Split-Path $f -Leaf)) -Force }

$styleText = [System.IO.File]::ReadAllText($sourceStyle)
$marker = 'HNB HYBRID BRANDED V20'
if ($styleText -notmatch '</style>') { throw 'Style PDF invalide: balise </style> absente.' }
if ($styleText -notmatch [regex]::Escape($marker)) {
  $extra = [System.IO.File]::ReadAllText((Join-Path $base 'V20_OVERRIDES.css'))
  $styleText = $styleText.Replace('</style>', "`r`n$extra`r`n</style>")
}

try {
  New-Item -Path $assetDir -ItemType Directory -Force | Out-Null
  Copy-Item (Join-Path $payload 'public\images\hnb-pdf-hybrid-v20\*') $assetDir -Force
  Copy-Item (Join-Path $payload 'resources\views\pdf\_header.blade.php') $sourceHeader -Force
  Copy-Item (Join-Path $payload 'resources\views\pdf\_footer.blade.php') $sourceFooter -Force
  [System.IO.File]::WriteAllText($sourceStyle, $styleText, (New-Object System.Text.UTF8Encoding $false))
  foreach ($f in @($sourceStyle,$sourceHeader,$sourceFooter)) {
    & php -l $f
    if ($LASTEXITCODE -ne 0) { throw "Erreur PHP : $f" }
  }
  & php artisan view:clear
  if ($LASTEXITCODE -ne 0) { throw 'view:clear a échoué' }
  & php artisan view:cache
  if ($LASTEXITCODE -ne 0) { throw 'view:cache a échoué' }
  Write-Host 'HNB PDF V20 installed: FULL HEADER LOGO preserved + premium footer.' -ForegroundColor Green
  Write-Host ('BACKUP: ' + $backup)
  Write-Host 'No controller, database, workflow or existing PDF data modified.'
} catch {
  foreach ($f in @($sourceStyle,$sourceHeader,$sourceFooter)) {
    [System.IO.File]::WriteAllBytes($f, $allOld[$f])
  }
  & php artisan view:clear | Out-Null
  Write-Host 'ERROR: Fichiers PDF restaurés depuis backup.' -ForegroundColor Red
  throw
}
