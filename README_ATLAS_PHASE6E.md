# HN BLOOM ATLAS AI — Phase 6E · Geofabrik data discovery

This phase solves the Overpass timeout by working **offline** against Geofabrik's official OpenStreetMap Morocco extract. It **does not** crawl companies' websites or provide verified phone/email leads. Existing CRM screens and routes stay unchanged.

## What it does

- Download the 450–550 MB Morocco free Shapefile archive from Geofabrik once, with explicit confirmation.
- Read OSM POI point layer (`gis_osm_pois_free_1.shp`/`.dbf`) directly out of the ZIP, using Python standard library only (no pip dependencies).
- Select named hotels, hospitals, schools and similar institutions, and security-related named POIs, within ~20km of supported cities.
- Produce up to 40 candidates per city with geographic OSM source URLs; category `client_final` is only a hypothesis, `installateur` is inferred from name; contact fields are intentionally blank.
- Call existing `prospects:import-csv` in **dry run only**. It never sends messages, deletes prospects, or changes CRM DB during the command.

## Install

```powershell
Expand-Archive "$env:USERPROFILE\Downloads\HN_BLOOM_ATLAS_AI_Phase6E_Geofabrik.zip" -DestinationPath . -Force
powershell -ExecutionPolicy Bypass -File .\INSTALL_ATLAS_PHASE6E.ps1
powershell -ExecutionPolicy Bypass -File .\ATLAS_DISCOVER_GEOFABRIK.ps1
```

First run requires Python 3 and enough disk/network allowance for ~500MB archive. A download prompt asks you to type OUI; otherwise no download. Archive is retained for subsequent offline runs.

When preview looks correct, import explicitly:

```powershell
php artisan prospects:import-csv atlas-osm-maroc.csv --source=geofabrik_osm --limit=500
php artisan atlas:radar --limit=30
```

The CSV contains no personal contact data; review POIs before using commercially. Geofabrik/OSM data may contain inaccurate or dated locations and names. Repeat extraction monthly (manually or using an approved scheduler after verification) to update the source file. The existing importer deduplicates by source ID and contact where available.

**Attribution**: © OpenStreetMap contributors, Open Database License (ODbL). Geofabrik provides the export at https://download.geofabrik.de/africa/morocco.html. Preserve attribution and comply with ODbL when redistributing a derived database.
