HN BLOOM V22 – Signature PDF design (Devis, Factures, Commandes, Livraisons, Reçus)

Install (in Laravel project root):
Expand-Archive "$env:USERPROFILE\Downloads\HNB_PDF_SIGNATURE_FINAL_V22.zip" -DestinationPath . -Force
powershell -ExecutionPolicy Bypass -File .\HNB_PDF_SIGNATURE_FINAL_V22\INSTALL_HNB_PDF_V22.ps1

Changes ONLY the three common PDF Blade partials, adds 2 decorative PNG files.
Existing data fields and document content are not removed. No DB changes, approvals or sending changes.

CAUTION: Browser views using different templates, e.g. purchase orders/pdfs outside pdf._style,
will not be changed by the shared partial. PHP syntax and Laravel Blade view caching are validated by installer.
