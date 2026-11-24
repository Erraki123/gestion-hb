HN BLOOM - V31 Chromium Footer Position Correction
Root cause: print renderer copied Header/Footer to separate Chrome page templates, but also left them in the source page DOM. Older print CSS may display/misposition the originals.
Fix: after capturing source HTML and styles, remove the original header.masthead and footer.page-footer from source DOM before printing. Chromium still prints the copied templates on every page.
Install from Laravel root:
powershell -ExecutionPolicy Bypass -File .\HNB_CHROME_FOOTER_POSITION_V31\INSTALL_HNB_FOOTER_V31.ps1
Changes render.cjs only, using a string-targeted patch with backup and a local PDF smoke test. Preserves V29 pagination, V26 Base64, V28 discount and V30 tax presentation. Requires V29 renderer installed.
