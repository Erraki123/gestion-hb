$ErrorActionPreference = 'Stop'
if (-not (Test-Path '.\artisan')) { throw 'Run from Laravel project root' }
if (-not (Test-Path '.\tools')) { New-Item -ItemType Directory '.\tools' | Out-Null }
if (-not (Test-Path '.\tests')) { New-Item -ItemType Directory '.\tests' | Out-Null }
Copy-Item '.\PATCH_ATLAS_6S\tools\atlas_smart_score.py' '.\tools\atlas_smart_score.py' -Force
Copy-Item '.\PATCH_ATLAS_6S\tests\test_atlas_smart_score.py' '.\tests\test_atlas_smart_score.py' -Force
python -m unittest discover -s '.\tests' -p 'test_atlas_smart_score.py' -v
if ($LASTEXITCODE -ne 0) { throw 'ATLAS 6S tests failed' }
Write-Host 'Phase 6S installed. No DB modifications, no API calls, no scheduler changes.' -ForegroundColor Green
Write-Host 'Run: python .\tools\atlas_smart_score.py' -ForegroundColor Cyan
