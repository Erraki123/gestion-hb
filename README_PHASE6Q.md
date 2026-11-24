# Phase 6Q — ATLAS local scheduled monitor (Windows)

This release makes the already working **Phase 6P local CSV monitor** easy to run automatically. It does **not** fetch fresh companies, update OSM archives, call DeepSeek, write to the CRM database or send emails/WhatsApp. If source files do not change, reports will show zero new companies.

## Installation

From Laravel project root in Windows PowerShell:

```powershell
Expand-Archive "$env:USERPROFILE\Downloads\HN_BLOOM_ATLAS_Phase6Q_Local_Scheduler.zip" -DestinationPath . -Force
powershell -ExecutionPolicy Bypass -File .\INSTALL_ATLAS_PHASE6Q.ps1
powershell -ExecutionPolicy Bypass -File .\RUN_ATLAS_PHASE6Q.ps1
```

Review `storage/app/prospect-imports/atlas-daily-status.json` and `storage/app/atlas-local-logs/`.

## Optional daily task (requires explicit execution)

```powershell
powershell -ExecutionPolicy Bypass -File .\ENABLE_ATLAS_DAILY.ps1
```

Runs at **09:30 Windows local time**, **only when this user is logged in**. It uses the current local CSV snapshot; no online scraping and no live updates. If you do not run this command, the installer will NOT schedule anything.

Disable immediately:

```powershell
powershell -ExecutionPolicy Bypass -File .\DISABLE_ATLAS_DAILY.ps1
```

The runner uses an OS-level lock, writes a dated local log and a last-run status JSON. Nothing is automatically imported to `prospects`, `clients`, or `discovered_prospects`.
