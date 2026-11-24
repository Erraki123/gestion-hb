<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Admin\CommercialController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientActivityController;
use App\Http\Controllers\ClientAttachmentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientNoteController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentDocumentController;
use App\Http\Controllers\TransportAlertController;
use App\Http\Controllers\PaymentExportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierInvoiceController;
use App\Http\Controllers\SupplierPaymentController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\GoodsReceiptController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware(['auth', 'active'])->group(function () {

    // Redirection selon le rôle
    Route::get('/dashboard', function () {
        return auth()->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('commercial.dashboard');
    })->name('dashboard');

    // Profil (Breeze) : pas de suppression de compte
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Clients (accessible aux admins et aux commerciaux)
    Route::middleware('role:admin,commercial')->group(function () {
        // ⚠️ Doit rester AVANT Route::resource('clients', ...),
        // sinon "search" serait interprété comme "clients/{client}".
        // CRM simple : prospects, étapes et relances
        Route::get('prospects', [\App\Http\Controllers\ProspectController::class, 'index'])->name('prospects.index');
        Route::post('prospects', [\App\Http\Controllers\ProspectController::class, 'store'])->name('prospects.store')->middleware('throttle:30,1');
        Route::patch('prospects/{client}', [\App\Http\Controllers\ProspectController::class, 'update'])->name('prospects.update');

        Route::get('clients/search', [ClientController::class, 'search'])
            ->middleware('throttle:60,1')
            ->name('clients.search');

        Route::get('clients/{client}/statement', [ClientController::class, 'statement'])->name('clients.statement');
        Route::resource('clients', ClientController::class);

        Route::post('clients/{client}/notes', [ClientNoteController::class, 'store'])
            ->name('clients.notes.store');
        Route::post('clients/{client}/activities', [ClientActivityController::class, 'store'])
            ->name('clients.activities.store');
        Route::post('clients/{client}/attachments', [ClientAttachmentController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('clients.attachments.store');

        Route::get('attachments/{attachment}', [ClientAttachmentController::class, 'show'])
            ->name('attachments.show');
        Route::delete('attachments/{attachment}', [ClientAttachmentController::class, 'destroy'])
            ->name('attachments.destroy');
    });

    // Produits et catégories : Admin = gestion complète
    // ⚠️ Ces routes doivent rester AVANT les routes de lecture,
    // sinon "products/create" serait interprété comme "products/{product}".
    Route::middleware('role:admin')->group(function () {
        Route::resource('products', ProductController::class)->except(['index', 'show']);
        Route::post('products/{product}/movements', [StockMovementController::class, 'store'])
            ->name('products.movements.store');

        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });

    // Produits : Admin + Commercial en lecture seule
    Route::middleware('role:admin,commercial')->group(function () {
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
    });

    // Devis, commandes, factures, paiements : Admin + Commercial
    Route::middleware('role:admin,commercial')->group(function () {
        // Devis
        // ⚠️ Doit rester AVANT Route::resource('quotes', ...)
        Route::get('quotes/products/search', [QuoteController::class, 'searchProducts'])
            ->middleware('throttle:60,1')
            ->name('quotes.products.search');

        Route::post('quotes/{quote}/approval-request', [\App\Http\Controllers\QuoteApprovalController::class, 'request'])->name('quotes.approval.request');
        Route::resource('quotes', QuoteController::class);
        Route::patch('quotes/{quote}/status', [QuoteController::class, 'status'])->name('quotes.status');
        Route::post('quotes/{quote}/duplicate', [QuoteController::class, 'duplicate'])->name('quotes.duplicate');
        Route::post('quotes/{quote}/convert', [QuoteController::class, 'convert'])->name('quotes.convert');
        Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');

        // Commandes
        // La liste des commandes est dans la page « Commandes » (quotes.index) : l'ancien lien redirige
        Route::get('orders', fn () => redirect()->route('quotes.index'))->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}/status', [OrderController::class, 'status'])->name('orders.status');
        Route::post('orders/{order}/approval-request', [\App\Http\Controllers\OrderApprovalController::class, 'request'])->name('orders.approval.request');
        Route::post('orders/{order}/deliveries', [DeliveryController::class, 'storeFromOrder'])->name('orders.deliveries.store');
        Route::post('orders/{order}/deliver', [DeliveryController::class, 'quick'])->name('orders.deliver');
        Route::post('orders/{order}/invoice', [InvoiceController::class, 'fromOrder'])->name('orders.invoice');

        Route::get('deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
        Route::get('deliveries/{delivery}', [DeliveryController::class, 'show'])->name('deliveries.show');
        Route::put('deliveries/{delivery}', [DeliveryController::class, 'update'])->name('deliveries.update');
        Route::delete('deliveries/{delivery}', [DeliveryController::class, 'destroy'])->name('deliveries.destroy');
        Route::post('deliveries/{delivery}/validate', [DeliveryController::class, 'validate'])->name('deliveries.validate');
        Route::get('deliveries/{delivery}/pdf', [DeliveryController::class, 'pdf'])->name('deliveries.pdf');

        // Factures
        Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show', 'update', 'destroy']);
        Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue');
        Route::post('invoices/{invoice}/credit', [InvoiceController::class, 'credit'])->name('invoices.credit');
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'payStore'])->name('invoices.pay.store');
        Route::patch('invoices/{invoice}/extras', [InvoiceController::class, 'extras'])->name('invoices.extras');
        Route::patch('invoices/{invoice}/transport-alarm', [TransportAlertController::class, 'update'])->name('invoices.transport-alarm');
        Route::get('alerts/transport', [TransportAlertController::class, 'index'])->name('transport.alerts');
        Route::post('payments/{payment}/documents', [PaymentDocumentController::class, 'store'])->name('payments.documents.store');
        Route::get('payment-documents/{document}', [PaymentDocumentController::class, 'show'])->name('payment-documents.show');
        Route::delete('payment-documents/{document}', [PaymentDocumentController::class, 'destroy'])->name('payment-documents.destroy');

        Route::get('returns', [ReturnController::class, 'index'])->name('returns.index');
        Route::post('invoices/{invoice}/returns', [ReturnController::class, 'store'])->name('invoices.returns.store');
        Route::get('returns/{customerReturn}', [ReturnController::class, 'show'])->name('returns.show');
        Route::put('returns/{customerReturn}', [ReturnController::class, 'update'])->name('returns.update');
        Route::delete('returns/{customerReturn}', [ReturnController::class, 'destroy'])->name('returns.destroy');
        Route::post('returns/{customerReturn}/validate', [ReturnController::class, 'validate'])->name('returns.validate');

        // Paiements et créances
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        // ⚠️ Doit rester AVANT payments/{payment}/pdf, sinon "export" serait pris pour un id de paiement.
        Route::get('payments/export/pdf', [PaymentExportController::class, 'pdf'])
            ->middleware('throttle:30,1')
            ->name('payments.export');
        Route::get('payments/{payment}/pdf', [PaymentController::class, 'pdf'])->name('payments.pdf');
        Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('invoices.payments.store');
        Route::get('receivables', [ReceivableController::class, 'index'])->name('receivables.index');
        Route::get('alerts/cheques', [\App\Http\Controllers\ChequeAlertController::class, 'index'])->name('cheques.alerts');
        Route::post('documents/{type}/{id}/send', [\App\Http\Controllers\DocumentSendController::class, 'store'])
            ->where(['type' => 'quote|invoice', 'id' => '[0-9]+'])->middleware('throttle:20,1')->name('documents.send');
    });

    Route::middleware('role:admin')->post('quotes/{quote}/approval-decision', [\App\Http\Controllers\QuoteApprovalController::class, 'decide'])->name('quotes.approval.decide');
    Route::middleware('role:admin')->post('orders/{order}/approval-decision', [\App\Http\Controllers\OrderApprovalController::class, 'decide'])->name('orders.approval.decide');

    // Admin seulement : achats, fournisseurs et paiements fournisseurs
    Route::middleware('role:admin')->group(function () {
        Route::get('achats', [PurchaseController::class, 'index'])->name('purchases.index');

        Route::resource('suppliers', SupplierController::class)->except(['create', 'edit']);
        Route::resource('purchase-orders', PurchaseOrderController::class);
        Route::post('purchase-orders/{purchaseOrder}/confirm', [PurchaseOrderController::class, 'confirm'])->name('purchase-orders.confirm');
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
        Route::get('purchase-orders/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'pdf'])->name('purchase-orders.pdf');
        Route::post('purchase-orders/{purchaseOrder}/receipts', [GoodsReceiptController::class, 'store'])->name('purchase-orders.receipts.store');
        Route::get('receipts/{receipt}', [GoodsReceiptController::class, 'show'])->name('receipts.show');
        Route::put('receipts/{receipt}', [GoodsReceiptController::class, 'update'])->name('receipts.update');
        Route::delete('receipts/{receipt}', [GoodsReceiptController::class, 'destroy'])->name('receipts.destroy');
        Route::post('receipts/{receipt}/validate', [GoodsReceiptController::class, 'validate'])->name('receipts.validate');
        Route::post('receipts/{receipt}/cancel', [GoodsReceiptController::class, 'cancel'])->name('receipts.cancel');

        Route::resource('supplier-invoices', SupplierInvoiceController::class);
        Route::post('supplier-invoices/{supplierInvoice}/cancel', [SupplierInvoiceController::class, 'cancel'])->name('supplier-invoices.cancel');
        Route::post('supplier-invoices/{supplierInvoice}/payments', [SupplierPaymentController::class, 'store'])->name('supplier-invoices.payments.store');

        Route::post('supplier-payments/{supplierPayment}/cancel', [SupplierPaymentController::class, 'cancel'])->name('supplier-payments.cancel');

        Route::post('payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
        Route::patch('payments/{payment}/cheque', [PaymentController::class, 'cheque'])->name('payments.cheque');
        Route::post('deliveries/{delivery}/cancel', [DeliveryController::class, 'cancel'])->name('deliveries.cancel');
    });

    // Commissions : Admin (gestion) — ⚠️ AVANT le groupe Admin + Commercial,
    // sinon "commissions/payouts/create" serait interprété comme "commissions/payouts/{payout}".
    Route::middleware('role:admin')->group(function () {
        Route::get('commissions/payouts/create', [CommissionController::class, 'payoutCreate'])->name('commissions.payouts.create');
        Route::post('commissions/payouts', [CommissionController::class, 'payoutStore'])->name('commissions.payouts.store');
    });

    // Commissions : Admin + Commercial (lecture)
    Route::middleware('role:admin,commercial')->group(function () {
        Route::get('commissions', [CommissionController::class, 'index'])->name('commissions.index');
        Route::get('commissions/payouts', [CommissionController::class, 'payouts'])->name('commissions.payouts');
        Route::get('commissions/payouts/{payout}', [CommissionController::class, 'payoutShow'])->name('commissions.payouts.show');
        Route::get('commissions/payouts/{payout}/pdf', [CommissionController::class, 'payoutPdf'])->name('commissions.payouts.pdf');
    });

    // Rapports et recherche : Admin + Commercial
    Route::middleware('role:admin,commercial')->group(function () {
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/{type}', [ReportController::class, 'show'])
            ->where('type', '[a-z]+')->name('reports.show');
        Route::get('reports/{type}/{format}', [ReportController::class, 'export'])
            ->middleware('throttle:30,1')
            ->where(['type' => '[a-z]+', 'format' => 'pdf|xlsx'])
            ->name('reports.export');

        Route::get('search', [SearchController::class, 'index'])
            ->middleware('throttle:60,1')
            ->name('search');
    });

    // Corbeille, journal d'activité et système : Admin seulement
    Route::middleware('role:admin')->group(function () {
        Route::get('trash', [TrashController::class, 'index'])->name('trash.index');
        Route::post('trash/{type}/{id}/restore', [TrashController::class, 'restore'])
            ->where(['type' => '[a-z]+', 'id' => '[0-9]+'])->name('trash.restore');

        Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');
        Route::get('accounting', [AccountingController::class, 'index'])->name('accounting.index');
        Route::get('accounting/download', [AccountingController::class, 'download'])
            ->middleware('throttle:10,1')
            ->name('accounting.download');

        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::get('system', [SystemController::class, 'index'])->name('system.index');
        Route::post('system/backup', [SystemController::class, 'backup'])
            ->middleware('throttle:3,1')
            ->name('system.backup');
    });

    // Espace Admin
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::get('/approvals', [\App\Http\Controllers\Admin\ApprovalCenterController::class, 'index'])->name('approvals.index');

        Route::resource('commerciaux', CommercialController::class)
            ->parameters(['commerciaux' => 'commercial'])
            ->except(['show']);
        Route::patch('commerciaux/{commercial}/toggle', [CommercialController::class, 'toggle'])
            ->name('commerciaux.toggle');
    });

    // Espace Commercial
    Route::middleware('role:commercial')->prefix('commercial')->name('commercial.')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'commercial'])->name('dashboard');
    });
});

// Lien PDF signé et temporaire (envoyé par WhatsApp) : pas de connexion requise
Route::get('d/{type}/{id}', [\App\Http\Controllers\PublicDocumentController::class, 'show'])
    ->middleware(['signed', 'throttle:30,1'])
    ->where(['type' => 'quote|invoice', 'id' => '[0-9]+'])
    ->name('documents.public');

require __DIR__ . '/auth.php';
require __DIR__ . '/ultramsg_phase3b.php';

require __DIR__ . '/commercial_ai_phase4a.php';

require __DIR__ . '/crm_ai_phase4b.php';

require __DIR__ . '/crm_ai_phase4c.php';

require __DIR__ . '/ai_floating_phase4d.php';

require __DIR__ . '/discovery_phase5b.php';
