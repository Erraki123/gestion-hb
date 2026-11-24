HN BLOOM Chromium PDF footer pagination hotfix V27.
Installs CSS only. Fixes original @page margin-bottom 28mm and .page-footer bottom:-22mm combination by keeping footer inside page and reserving 16mm content space.
Backups are generated automatically.
Run from Laravel root: powershell -ExecutionPolicy Bypass -File .\HNB_CHROMIUM_FOOTER_SINGLE_PAGE_V27\INSTALL_HNB_FOOTER_V27.ps1
Test short quote PDF and long multi-page invoice PDF.
