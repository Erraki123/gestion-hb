<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Admin\CommercialController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientActivityController;
use App\Http\Controllers\ClientAttachmentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientNoteController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\CommissionRuleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TrashController;
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
        Route::resource('quotes', QuoteController::class);
        Route::patch('quotes/{quote}/status', [QuoteController::class, 'status'])->name('quotes.status');
        Route::post('quotes/{quote}/duplicate', [QuoteController::class, 'duplicate'])->name('quotes.duplicate');
        Route::post('quotes/{quote}/convert', [QuoteController::class, 'convert'])->name('quotes.convert');
        Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');

        // Commandes
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}/status', [OrderController::class, 'status'])->name('orders.status');
        Route::post('orders/{order}/invoice', [InvoiceController::class, 'fromOrder'])->name('orders.invoice');

        // Factures
        Route::resource('invoices', InvoiceController::class)->only(['index', 'show', 'update', 'destroy']);
        Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue');
        Route::post('invoices/{invoice}/credit', [InvoiceController::class, 'credit'])->name('invoices.credit');
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

        // Paiements et créances
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/{payment}/pdf', [PaymentController::class, 'pdf'])->name('payments.pdf');
        Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('invoices.payments.store');
        Route::get('receivables', [ReceivableController::class, 'index'])->name('receivables.index');
    });

    // Admin seulement : annulation et suivi des chèques
    Route::middleware('role:admin')->group(function () {
        Route::post('payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
        Route::patch('payments/{payment}/cheque', [PaymentController::class, 'cheque'])->name('payments.cheque');
    });

    // Commissions : Admin (gestion) — ⚠️ AVANT le groupe Admin + Commercial,
    // sinon "commissions/payouts/create" serait interprété comme "commissions/payouts/{payout}".
    Route::middleware('role:admin')->group(function () {
        Route::get('commission-rules', [CommissionRuleController::class, 'index'])->name('commission-rules.index');
        Route::get('commission-rules/{commercial}', [CommissionRuleController::class, 'show'])->name('commission-rules.show');
        Route::post('commission-rules/{commercial}', [CommissionRuleController::class, 'store'])->name('commission-rules.store');

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

require __DIR__ . '/auth.php';