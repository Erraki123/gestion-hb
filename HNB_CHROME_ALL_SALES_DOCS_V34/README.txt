HN BLOOM - Chromium Premium ALL CLIENT DOCUMENTS

Install from Laravel project root:
  Expand-Archive "$env:USERPROFILE\Downloads\HNB_CHROME_ALL_SALES_DOCS_V34.zip" -DestinationPath . -Force
  powershell -ExecutionPolicy Bypass -File .\HNB_CHROME_ALL_SALES_DOCS_V34\INSTALL_HNB_ALL_TYPES.ps1

New/rewired routes:
- GET /quotes/{quote}/pdf => Chromium DEVIS (existing bridge)
- GET /quotes/{quote}/pdf?doc=commande => Chromium BON DE COMMANDE (linked order)
- GET /invoices/{invoice}/pdf => Chromium FACTURE (existing bridge)
- GET /orders/{order}/pdf => Chromium BON DE COMMANDE (new route)
- GET /deliveries/{delivery}/pdf => Chromium BON DE LIVRAISON
- GET /payments/{payment}/pdf => Chromium RECU
- GET /admin/pdf-studio/{type}/{id}?download=1 => quote/order/invoice/delivery/receipt

Admin direct use preserved. Commercial downloads require director approvals according to existing Studio guard.
Existing Chrome Blade/CSS, footer fix V33, TVA in totals and conditional discount column unchanged.
Only local PDF endpoints change; automatic Email/WhatsApp and attachment generation through DocumentSender remain as they were and must be audited separately before switching.
Backup and rollback if tests fail. Does not change business data, pricing, stock, approval records.
This pack does not change supplier purchase orders / accounting exports.

QA IMPORTANT:
1. Admin: original PDF links of Devis, Invoice, BL, Receipt; and order /orders/ID/pdf.
2. Commercial: own approved quote, approved order, issued/released invoice, approved delivery and receipt, and unapproved cases 403.
3. PDF 1/15/40 items => repeated header/footer; no blank pages.
4. Existing customer delivery and messaging paths require separate QA.
