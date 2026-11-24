$ErrorActionPreference = 'Stop'
$srv  = 'u451019543@77.37.37.93'
$port = 65002
$zip  = Join-Path $env:TEMP 'gestion-update.zip'

Write-Host '1/4 Compilation CSS/JS...' -ForegroundColor Cyan
npm run build

Write-Host '2/4 Creation du zip...' -ForegroundColor Cyan
if (Test-Path $zip) { Remove-Item $zip -Force }
tar -a -cf $zip --exclude=./node_modules --exclude=./vendor --exclude=./.git --exclude=./.env --exclude=./storage --exclude=./bootstrap/cache --exclude=./public/hot --exclude=./public/storage --exclude=./public/index.php --exclude=*.ps1 --exclude=*.zip .

Write-Host '3/4 Envoi au serveur...' -ForegroundColor Cyan
scp -P $port $zip "${srv}:~/gestion-update.zip"

Write-Host '4/4 Installation sur le serveur...' -ForegroundColor Cyan
ssh -p $port $srv 'bash ~/deploy.sh'
