$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Run from Laravel project root' }
$folder = 'storage\app\prospect-imports'
New-Item -ItemType Directory -Force -Path $folder | Out-Null
python -m unittest discover -s '.\tests' -p 'test_atlas_local_digest.py' -v
if ($LASTEXITCODE -ne 0) { throw 'Phase6P tests failed' }
Write-Host 'ATLAS Phase 6P installed. Run .\RUN_ATLAS_PHASE6P.ps1 to generate a local digest.' -ForegroundColor Green
