# HN BLOOM ATLAS — Phase 6K: Contact Evidence Matching (Local)

**Purpose:** Prepare safe, source-backed contact enrichment without an API key. Zero external requests, zero CRM database writes. Does not fabricate contacts or search the web. This phase prepares the provider/source integration.

## Install
Extract ZIP into the root of `gestion-commerciale`, run:

```powershell
powershell -ExecutionPolicy Bypass -File .\INSTALL_ATLAS_PHASE6K.ps1
```

Inputs:
- `storage/app/prospect-imports/atlas-smart-preselection.csv` (from Phase 6F)
- optional `storage/app/prospect-imports/atlas-contact-evidence.csv`, externally provided professional contact data from authorized sources, with columns `company,city,phone,email,website,evidence_url`

Output: `storage/app/prospect-imports/atlas-contact-review.csv` with `contact_status`, `contact_evidence_url`, `contact_note`.

No evidence file? All 60 candidates remain `missing`, intentionally. Need a genuine data provider or public professional sources for real contacts; DeepSeek cannot supply verified contacts by itself.

`review_required` is a tentative match, **not verification**. The source must be checked, permission/license respected, and human validation required before any contact information is used. If the same normalized name and city occur in multiple candidate rows, the match is ambiguous. Existing contact fields are never overwritten. Never import this output to CRM automatically.

## Next
Connect an authorized business contact provider with compatible reuse/storage terms, or a permitted public-source collector with limited requests, then let ATLAS run this matching step. Add verification UI/actions only after reviewing API terms and sample outputs.
