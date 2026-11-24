HN BLOOM FINAL SPRINT D - PUBLIC DOCUMENT RELEASE SAFETY

After Sprint C, existing public PDF links are signed but refer to mutable document IDs.
A document edited after sending can expose the changed PDF using the old link.

This patch:
- Rechecks director approval on the public PDF endpoint.
- Binds signed URLs to SHA-256 fingerprint of current approved content and recipient.
- Rejects old signed links lacking 'v' and links for changed/unapproved versions (HTTP 403).
- Adds private/no-store response header.
- Does not modify database, send messages, or touch Scheduler.

Install in project root:
Expand-Archive "$env:USERPROFILE\Downloads\HN_BLOOM_FINAL_SPRINT_D_LINK_SAFETY.zip" -DestinationPath . -Force
powershell -ExecutionPolicy Bypass -File .\INSTALL_FINAL_SPRINT_D.ps1

IMPORTANT: Previously sent links will stop working until re-sent with a new valid link.
Before production, test the full approval workflow, the signed link, Commercial denial,
quote price approval, invoice state changes, scheduled jobs and actual external providers.
This is a narrow security fix, not proof of complete production readiness.
