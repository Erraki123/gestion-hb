# HN BLOOM ATLAS 6L — OSM PBF Contact Evidence

Local only. This patch extracts contact:phone / phone, contact:email / email and contact:website / website **where available** in the official raw OSM PBF. The previous Geofabrik shapefile did not contain these tags. It matches candidate OSM numeric IDs **and exact normalized business names**. Same numeric OSM ID across different object types is treated as ambiguous, not accepted. It will not fabricate contacts, validate ownership, or contact any company.

1. Expand this ZIP in the Laravel project root.
2. `powershell -ExecutionPolicy Bypass -File .\INSTALL_ATLAS_PHASE6L.ps1` (runs offline unit tests).
3. `powershell -ExecutionPolicy Bypass -File .\RUN_ATLAS_PHASE6L.ps1` (asks consent to download an additional ~230 MB OSM PBF file; may need to install Python package osmium with consent).
4. Review `storage/app/prospect-imports/atlas-osm-pbf-evidence.csv`. **Do not auto-import.** Only some POIs may have any contact tags; zero results is a legitimate outcome.

This is a source acquisition step. Linking the new evidence through phase 6K requires copying it to the 6K evidence path **after review** and running its matching script. Phase 6K's comparison is name+city, and does not independently prove the phone belongs to the company; verify manually before a real CRM update. There are no database updates, outgoing messages, AI API calls or deployments.

Source attribution: © OpenStreetMap contributors, ODbL; data from Geofabrik. Comply with the attribution and ODbL requirements for any derived published data.
