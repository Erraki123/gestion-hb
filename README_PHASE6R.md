# HN BLOOM ATLAS — Phase 6R (local, source freshness)

## Goal
The existing Phase 6Q Windows daily task only monitors local CSV snapshots. 6R adds a lightweight network check against the official Geofabrik Morocco SHP ZIP (HEAD request); it does **not** pretend that monitoring local CSV discovers fresh prospects.

## Install
Extract the ZIP into Laravel project root with -Force; execute `INSTALL_ATLAS_PHASE6R.ps1`. It only runs four Python unit tests.

## Check freshness (one network HEAD)
`powershell -ExecutionPolicy Bypass -File .\CHECK_ATLAS_SOURCE.ps1`

The first check records a metadata baseline and returns `source_updated_since_previous_check: null`; subsequent checks compare server ETag/Last-Modified. Server timestamp changing is **not proof** that new client leads exist. `atlas-source-observed.json` is only a metadata observation, **not verification** that the local archive matches server. No task is automatically registered or altered.

## Staged download (optional and explicit consent)
`powershell -ExecutionPolicy Bypass -File .\PREVIEW_ATLAS_SOURCE_REFRESH.ps1`

Type `OUI` to fetch ~470 MB and extract a *new* candidate CSV into `storage/app/prospect-imports/atlas-refresh-preview/`. Existing `atlas-osm-maroc.csv`, selection, contact evidence, approvals and CRM database remain untouched. A report contains source SHA256 and candidate counts. Download requires Internet and disk space (~1 GB temporary + data), and can fail; staging is never automatically imported. Data limitations remain: at most 40 candidate POIs per city and no verified contacts.

## Safety & scope
Does not modify Windows Scheduler task created in 6Q; does not launch imports, send messages or update CRM. Does not require DeepSeek or Google API keys. Always use the official OSM ODbL attribution. External network calls limited to explicit check and staged download. Never automate a full national download daily.
