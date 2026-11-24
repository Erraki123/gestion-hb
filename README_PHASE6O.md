# HN BLOOM — ATLAS Phase 6O: Manual Contact Review

## Adds
- Review of contact suggestions in existing **Voir la fiche** modal (admin only).
- Buttons **Marquer comme contrôlé** (requires source checkbox) and **Écarter**.
- Review ledger in `atlas_contact_decisions` (one record per discovered prospect; audit actor, time, and fingerprint).
- Review is tied to a SHA-256 fingerprint of suggested phone/email/website/evidence URL: it expires as soon as the evidence changes.
- Does **not** copy contacts into master Prospects/Clients, claim independent verification, or send communications.
- Existing source OSM citations remain available.

## Install
Extract ZIP in Laravel project root. Run:

```powershell
powershell -ExecutionPolicy Bypass -File .\INSTALL_ATLAS_PHASE6O.ps1
```

Then `http://127.0.0.1:8000/prospects` > Voir la fiche > Clinique Sebou. Review source first.

## Requirements
Phase 6N and `atlas-contact-review.csv` already generated. Laravel DB configured locally.

## Backup and safety
Installer creates `backup-atlas-phase6o-...` and `ATLAS_PHASE6O_BACKUP_PATH.txt`. It creates only one new migration table. Never blindly run migration rollback on an active CRM. The user should inspect backup and DB changes before any rollback. Changes to source CSV do not silently retain old approval.

## Caveats
A human checking OSM proves only the source was consulted, **not** that the contact details are current or belong to the right decision maker. Independent contact verification and API-sourced data require a later phase. Rejected/checked state is an audit ledger, not proof of identity. No API key or deployment needed.
