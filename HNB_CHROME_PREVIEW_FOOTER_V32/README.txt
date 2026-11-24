HN BLOOM - Preview footer V32

Why: V31 is installed in render.cjs and works for the PDF engine on sample tests, but the Blade stylesheet still contains old @media screen rules making footer placement wrong in browser HTML preview.

This patch ONLY adds @media screen CSS to resources/views/pdf/chrome/style.blade.php.
- Short HTML previews show footer at bottom of A4.
- Long previews keep footer after content.
- @media print, PDF renderer, approval rules, TVA/Remise and business data remain untouched.
- Backups are saved as backup-hnb-preview-footer-v32-TIMESTAMP.

Installation:
  cd "C:\Users\hp\Desktop\hblom\gestion-commerciale"
  Expand-Archive "$env:USERPROFILE\Downloads\HNB_CHROME_PREVIEW_FOOTER_V32.zip" -DestinationPath . -Force
  powershell -ExecutionPolicy Bypass -File .\HNB_CHROME_PREVIEW_FOOTER_V32\INSTALL_HNB_PREVIEW_FOOTER_V32.ps1

Test HTML preview WITHOUT download=1 then downloaded PDF with download=1.
If the downloaded PDF still has incorrect footer placement, upload the freshly generated PDF; this preview-only patch does not change the PDF renderer.
