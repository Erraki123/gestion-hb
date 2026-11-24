HN BLOOM - UTF8 HOTFIX V2
Run in project directory:
Expand-Archive "$env:USERPROFILE\Downloads\HN_BLOOM_Hotfix_UTF8_v2.zip" -DestinationPath . -Force
powershell -ExecutionPolicy Bypass -File .\FIX_PROSPECTION_ENCODING_V2.ps1
Refresh browser with Ctrl+F5.
This patch only changes recognised mojibake in two Prospection Blade templates.
Backups are made before any modification, no database operations.
If "Files repaired: 0" but browser still shows mojibake, inspect HTTP Content-Type charset and layout meta charset.
