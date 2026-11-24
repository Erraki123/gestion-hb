HN BLOOM Chromium PDF — original selected HTML/CSS design.
Run from Laravel root:
Expand-Archive "$env:USERPROFILE\Downloads\HNB_CHROMIUM_ADMIN_COMMERCIAL_APPROVED.zip" -DestinationPath . -Force
powershell -ExecutionPolicy Bypass -File .\HNB_CHROMIUM_ADMIN_COMMERCIAL_APPROVED\INSTALL_HNB_CHROMIUM_ACCESS.ps1

Admin: view/download all documents.
Commercial: own records only; preview internal with warning; PDF download only after approvals.
No routes for public access, no automatic sending, no DB changes.
This pack does not replace old DomPDF download buttons automatically.
