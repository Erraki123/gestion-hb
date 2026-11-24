<?php

use App\Http\Controllers\Admin\HnbChromePdfController;
use Illuminate\Support\Facades\Route;

// Studio preview for admin and owner commercial; downloads protected in controller.
Route::middleware(['web', 'auth', 'active', 'role:admin,commercial'])
    ->get('admin/pdf-studio/{type}/{id}', [HnbChromePdfController::class, 'show'])
    ->where(['type' => 'quote|order|invoice|delivery|receipt', 'id' => '[0-9]+'])
    ->name('admin.pdf-studio.show');

// HNB_ALL_TYPES_V34: client bon de commande, same auth + director approval inside Studio.
Route::middleware(['web', 'auth', 'active', 'role:admin,commercial'])
    ->get('orders/{order}/pdf', function (\App\Models\Order $order) {
        return redirect()->route('admin.pdf-studio.show', ['type' => 'order', 'id' => $order->id, 'download' => 1]);
    })->name('orders.pdf');
