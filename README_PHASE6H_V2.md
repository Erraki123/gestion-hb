HN BLOOM - ATLAS Phase 6H V2 (Local Only)

Fix: Windows-safe normalization for accented French terms in company and product names.
Fix: preserve Phase 6D product suggestions for unrecognized sectors (marked relevant=false).
Scope: one Laravel service, one unit test, optional narrowly-scoped Blade details injection.
No DB changes, imports, external APIs, messages or hosting changes.

From CRM project root in PowerShell:
Expand-Archive "$env:USERPROFILE\Downloads\HN_BLOOM_ATLAS_Phase6H_Fix_V2.zip" -DestinationPath . -Force
powershell -ExecutionPolicy Bypass -File .\INSTALL_ATLAS_PHASE6H_V2.ps1
php artisan atlas:radar --dry-run --limit=5

The installer makes a timestamped backup and restores existing files if any test fails.
It runs both legacy AtlasOpportunityServiceTest and AtlasSectorRecognitionTest.
Do not rerun the old INSTALL_ATLAS_PHASE6H.ps1.
