# HN BLOOM — ATLAS Phase 6J: Contact Enrichment preparation

## What this actually does
- Checks local coverage of professional phone/email/website on `atlas-smart-preselection.csv`.
- Conservative company and city matching to prepare contact lookups.
- Optional **live, ephemeral** Google Places Text Search (New) lookup of 1–5 businesses, only when API key is configured and user explicitly opts in to potentially billable requests.
- No DB migration; no outbound marketing; no contact invented; no Google Places data saved.

## Setup
1. Extract ZIP into Laravel project root.
2. Run `powershell -ExecutionPolicy Bypass -File .\INSTALL_ATLAS_PHASE6J.ps1`.

## Offline audit anytime
`python .\tools\atlas_contact_audit.py`

## When API key is available (optional, later)
Set `$env:ATLAS_GOOGLE_PLACES_KEY` for current PowerShell session using a secure input method, then:
`python .\tools\atlas_contact_audit.py --provider google --limit 2 --confirm-paid-requests`

**Important:** Google Places data storage and caching are restricted; place IDs have a specific exception. Google branding/attribution is required for displaying Places data in the CRM, which this phase does not yet do. Do not persist or mass-export Google Places phones/websites into CRM. Places may return phone and website, but not company email. Verify the match manually. Billing/quota limits apply. Do not commit API keys.

## Future work
- Choose an authorized contact data provider permitting CRM storage and commercial reuse.
- Integrate verified licensed provider records into Laravel database using an explicit review workflow.
- Build contact provenance and timestamp in company modal and end-to-end tests.
- DeepSeek optional AI classification later; it cannot make up or independently verify contact data.
