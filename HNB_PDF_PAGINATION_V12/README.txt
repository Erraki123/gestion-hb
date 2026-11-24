Fix V12 - removes risky full-A4 decorative elements creating blank PDF pages.
Does not change logo, PDF data, prices, CRM, or workflows.

Use in root Laravel folder:
Expand-Archive "$env:USERPROFILE\Downloads\HNB_PDF_PAGINATION_V12.zip" -DestinationPath . -Force
powershell -ExecutionPolicy Bypass -File .\HNB_PDF_PAGINATION_V12\INSTALL_HNB_PDF_V12.ps1

A freshly generated PDF must be used for testing.
If the page count is still wrong, share current resources/views/pdf/_header.blade.php, _footer.blade.php and _style.blade.php for a targeted fix.
