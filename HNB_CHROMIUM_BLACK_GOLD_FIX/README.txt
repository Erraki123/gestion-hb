Chromium PDF Studio - Black + Gold + White CSS fix.
Targets ONLY resources/views/pdf/chrome/style.blade.php.
No changes to controllers, database, document fields, approvals or legacy DomPDF templates.
Install from the Laravel root:
  Expand-Archive "$env:USERPROFILE\Downloads\HNB_CHROMIUM_BLACK_GOLD_FIX.zip" -DestinationPath . -Force
  powershell -ExecutionPolicy Bypass -File .\HNB_CHROMIUM_BLACK_GOLD_FIX\INSTALL_HNB_CHROMIUM_BLACK_GOLD.ps1
Then Ctrl+F5 HTML preview and re-generate PDF.
