HN BLOOM Chromium PDF V30 — TVA in totals only

Requirements: V28 installed (conditional Remise).
Changes only resources/views/pdf/chrome/document.blade.php.
Removes TVA table heading and each row's TVA percentage.
Existing TVA totals remain unchanged and computed by Laravel, including multiple rates.
Delivery table remains unchanged. No invoice calculations, controller, approvals, or database changes.

Install from Laravel root:
Expand-Archive "$env:USERPROFILE\Downloads\HNB_CHROME_TVA_TABLE_V30.zip" -DestinationPath . -Force
powershell -ExecutionPolicy Bypass -File .\HNB_CHROME_TVA_TABLE_V30\INSTALL_HNB_V30.ps1
