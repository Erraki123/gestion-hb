HN BLOOM V17 EXECUTIVE PDF
Install: Expand-Archive ZIP -DestinationPath . -Force ; powershell -ExecutionPolicy Bypass -File .\HNB_PDF_EXECUTIVE_V17\INSTALL_HNB_PDF_EXECUTIVE_V17.ps1
Only PDF styling and invoice PDF layout are changed. No controllers, migrations or business rules. Safe backup and rollback included.
Requires unchanged baseline invoices/pdf.blade.php; if customized, installer stops to prevent losing business fields.
