HN BLOOM V7 - ZAKHRAFA / FILIGRANE GOLD
=====================================
Adds a genuine transparent A4 decorative frame with geometric arabesque filigree on all four corners, thin side motifs and lower flourish. Gold hue #D4A332 matches the existing HN BLOOM logo.
Only updates the PDF presentation layer:
 resources/views/pdf/_header.blade.php
 resources/views/pdf/_footer.blade.php
 resources/views/pdf/_style.blade.php
 public/images/hnb-v7-zakhrafa-frame.png
 public/images/hnb-v7-zakhrafa-seal.png

Install from the Laravel root:
  Expand-Archive "$env:USERPROFILE\Downloads\HNB_PDF_ZAKHRAFA_V7.zip" -DestinationPath . -Force
  powershell -ExecutionPolicy Bypass -File .\INSTALL_HNB_PDF_ZAKHRAFA_V7.ps1

Creates full backup and restores on error. Does not edit any business data, document templates, controller, DB or workflows.
Generate NEW PDF after installation; test a 1-row invoice and a multi-page invoice. PDF is static, not animated.
