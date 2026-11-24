# ATLAS Phase 6P — Local opportunity monitoring

A non-destructive one-command offline pipeline. It reruns existing 6F smart selection from cached OSM CSV, runs 6K contact matching if existing 6L evidence is present, and generates a JSON digest showing newly selected/changed candidates since the previous run. It does not access network, database, APIs, or send messages. A first run sets the baseline, so all 60 records count as new on that run. No automatic background scheduler is installed; use Windows Task Scheduler only if you expressly want it, and note it will not discover new businesses unless underlying CSV files are updated.

1. Extract ZIP over Laravel project root.
2. Run `powershell -ExecutionPolicy Bypass -File .\INSTALL_ATLAS_PHASE6P.ps1`.
3. Run `powershell -ExecutionPolicy Bypass -File .\RUN_ATLAS_PHASE6P.ps1`.
4. See `storage/app/prospect-imports/atlas-local-digest.json`.

Reports are not verified business opportunities; unchanged source CSV implies no fresh discovery. The installer does not touch database records or override previous app PHP/Blade files.
