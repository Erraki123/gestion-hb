HN BLOOM - Chromium Legacy PDF Bridge

Purpose: Existing quote/invoice PDF buttons follow existing URLs and redirect to the approval-checked Chromium PDF Studio with download=1.

Installation (PowerShell, Laravel project root):
Expand-Archive "$env:USERPROFILE\Downloads\HNB_CHROME_LEGACY_BRIDGE.zip" -DestinationPath . -Force
powershell -ExecutionPolicy Bypass -File .\HNB_CHROME_LEGACY_BRIDGE\INSTALL_HNB_CHROME_BRIDGE.ps1

Change scope: only QuoteController::pdf and InvoiceController::pdf redirect.
Does NOT change external public signed links or DocumentSender (still DomPDF), delivery PDFs, receipts or old third-party mail attachments. No database changes.
Caution: approval logic is stricter in Studio than legacy route for commercial; documents pending director release will be blocked by 403 by design.
Installer refuses to change unknown code, creates a timestamped backup, checks php -l, and rolls back if validation fails.
