# HN BLOOM - ATLAS Company Sheet (local phase 6I)

Adds a native accessible-ish HTML dialog (Escape to dismiss, close buttons), replacing expanded inline ATLAS details, with company profile, provenance, phone/email/site when stored, sector hypothesis, data quality, and actual catalog matches.

**No enrichment or new contact data.** OSM rows without phone/email/site remain explicitly “Non disponible”. No automatic contact and no backend changes.

Install inside Laravel project root:

```powershell
Expand-Archive "$env:USERPROFILE\Downloads\HN_BLOOM_ATLAS_Phase6I_Fiche_Entreprise.zip" -DestinationPath . -Force
powershell -ExecutionPolicy Bypass -File .\INSTALL_ATLAS_MODAL.ps1
```

Open /prospects as admin; click Voir la fiche. Native browser dialog: close button or Escape. Styles scoped to atlas-sheet. Patches `resources/views/prospects/_discovery_review.blade.php` with backup. PHP adapter requires exactly the 6G review details block; can work after 6H injection. No migrations.

If Blade compilation fails, installer restores backup. For manual revert check `ATLAS_MODAL_BACKUP_PATH.txt`.
