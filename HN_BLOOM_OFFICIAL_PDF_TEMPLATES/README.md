# HN BLOOM — Official PDF Templates Pack

## Contents
- `previews/`: 5 populated A4 PDFs with **fictitious data** to review design. They are *not invoices*.
- `assets/hnbloom_logo_reference.png`: crop from the user-supplied image; replace with the original vector/transparent logo for production print quality.
- `laravel/resources/views/pdf/hnbloom-official.blade.php`: dynamic common Blade view for DOMPDF.
- `laravel/public/images/hnbloom_logo_reference.png`: logo asset loaded by Blade.

## Installation (Windows PowerShell, project root)
1. Expand ZIP in a **temporary directory** and review.
2. Copy the contents of `laravel/resources/views/pdf` to your Laravel `resources/views/pdf` folder, and the image under `laravel/public/images` to `public/images`.
3. Run `php artisan view:clear` and `php artisan view:cache`.
4. Test **one document in a local preview route, without changing live sending controllers**.

## Usage (only inside existing authorized, approval-checked PDF-generation flow)
```php
// Existing $doc array contract used by resources/views/pdf/document.blade.php:
$pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.hnbloom-official', ['doc' => $doc]);
return $pdf->stream('preview.pdf');
```

This pack does NOT alter existing routes, controllers, notification/sending logic or approval requirements. It is a visual/template asset pack. The current invoice/quote/payment/delivery PDF controllers each use different model variables and must be mapped deliberately to the `$doc` array before enabling the view in those flows. Never route draft previews through a public download or client send endpoint. `status: brouillon` can be watermarked separately; review the PDF authorization policy before production.

## Supported `$doc` array
- `type`: devis | facture | commande | bon_livraison | recu
- `title`, `number`, `date`, `status`
- `client`: `{name,address,city,ice}`
- `items`: `[ {sku,name,quantity,unit,unit_price,total_ht}, ... ]`
- `subtotal`, `tax`, `total`, `currency` (do **not** hardcode TVA 20%)
- `terms`, `notes`
- for recu: `invoice_number`, `payment_method`, `payment_reference`, `paid`

## Production checks
- Must use real company logo, consistent address/contact from `config('commerce.company')` (the reference screenshot differs from some old project defaults).
- Check approval for quote/facture prior to *final* PDF retrieval, and separately approval to send clients.
- Multi-page table rendering needs explicit tests with 20+ lines in your installed DOMPDF version; this pack includes a first-page visual design, not a certified long-document pagination engine.
- No stamp/signature box is included; the reference's signature box was intentionally not copied in accordance with earlier HN BLOOM invoice design requirement.
- Print preview PDFs are samples with fictional clients/prices, not legal fiscal documents.
