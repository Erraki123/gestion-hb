HN BLOOM Chromium Footer Fixed V33
==================================
Fixes the PDF OUTPUT (not merely HTML preview): real user source render.cjs copied @page and @media CSS into the Chromium header/footer printing templates. This can cause the printed footer to render near the top (under the logo).
Replaces only tools/pdf-chrome/render.cjs, using isolated CSS for the repeated print header/footer.
Keeps Page.printToPDF transferMode=ReturnAsBase64 (V26), repeated header/footer (V29), and the source document/CRM data untouched.
Installation (PowerShell, from Laravel root):
Expand-Archive "$env:USERPROFILE\Downloads\HNB_CHROME_FOOTER_ISOLATED_V33.zip" -DestinationPath . -Force
powershell -ExecutionPolicy Bypass -File .\HNB_CHROME_FOOTER_ISOLATED_V33\INSTALL_HNB_FOOTER_V33.ps1

Validation: download a NEW Devis PDF and review that Footer is at A4 bottom on all pages. The included test HTML uses example company data and is not from CRM.
Backup location is printed by the installer. If a check fails, installer restores it automatically. No emails or WhatsApp triggered.
