HN BLOOM - HTML/CSS + Chromium PDF Studio
=======================================

PURPOSE
An isolated, Admin-only preview/download for 4 PDF types, using live Laravel data and browser-quality CSS.
Does NOT replace existing quote/invoice/delivery/receipt PDFs or send routes.
Does NOT change commercial approval workflows, databases or stock.

INSTALL
1. Unzip HNB_CHROMIUM_PREMIUM_FINAL.zip into project root (Laravel artisan folder).
2. PowerShell in project root:
   powershell -ExecutionPolicy Bypass -File .\HNB_CHROMIUM_PREMIUM_FINAL\INSTALL_HNB_CHROMIUM.ps1
3. Login as Admin.
4. Preview URLs, using ACTUAL record IDs:
   /admin/pdf-studio/quote/45
   /admin/pdf-studio/invoice/29
   /admin/pdf-studio/delivery/1
   /admin/pdf-studio/receipt/1
5. Append ?download=1 to download PDF; must have Node.js + Edge or Chrome available.

TECH
Requires Node.js, npm, Chromium-based Edge or Chrome, and npm connectivity at installation.
Node module puppeteer-core is installed ONLY into tools/pdf-chrome/.
No Composer package added. No browser download required if Edge/Chrome installed.
On Linux hosting must install Chrome/Chromium and set HNB_CHROME_PATH for the PHP process.

SAFETY
Only Admin preview. No commercial permissions have been broadened. No original routes changed.
All incoming ID routes are constrained and authenticated; private temp HTML/PDF is deleted after printing.
Only the exact Blade business data available in the source controllers is shown; verify the output.

Important: this is a tested DESIGN PREVIEW path. Do not replace production PDF routes
until comparing it against the original content and approval policies.
