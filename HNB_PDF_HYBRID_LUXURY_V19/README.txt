HN BLOOM HYBRID LUXURY V19
=========================

This is actual Laravel Blade + CSS + two small ornamental PNGs.
The product, tax, terms, reference, payment, and account fields remain in the original dynamic document views.

Install from the Laravel project root in Windows PowerShell:
  Expand-Archive "$env:USERPROFILE\Downloads\HNB_PDF_HYBRID_LUXURY_V19.zip" -DestinationPath . -Force
  powershell -ExecutionPolicy Bypass -File .\HNB_PDF_HYBRID_LUXURY_V19\INSTALL_HNB_HYBRID_V19.ps1

What changes:
  resources/views/pdf/_header.blade.php
  resources/views/pdf/_style.blade.php
  resources/views/pdf/_footer.blade.php
  public/images/hnb-pdf-hybrid/header-ribbon.png
  public/images/hnb-pdf-hybrid/bottom-wave.png

The installer backs up overwritten files and rolls back if PHP or view compilation fails.
It does not modify invoice/quote/delivery/receipt Blade data templates, controllers, permissions or databases.
The sample reference invoice file is NOT installed.

Check generated NEW PDFs, not previously downloaded PDFs. Verify both one-line and many-line documents in DomPDF.

If a blank page is introduced, restore the 3 prior partials from the backup folder shown by the installer and run php artisan view:clear && php artisan view:cache.
