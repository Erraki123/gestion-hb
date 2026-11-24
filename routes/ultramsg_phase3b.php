<?php

use App\Http\Controllers\UltraMsgWebhookController;
use App\Http\Controllers\Admin\WhatsAppInboxController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

// UltraMsg cannot provide Laravel CSRF tokens; protected by a long secret URL.
Route::post('integrations/ultramsg/inbound/{secret}', [UltraMsgWebhookController::class, 'receive'])
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->middleware('throttle:120,1')
    ->where('secret', '[A-Za-z0-9_-]{32,128}')
    ->name('integrations.ultramsg.inbound');

Route::middleware(['auth', 'active', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('whatsapp-inbox', [WhatsAppInboxController::class, 'index'])->name('whatsapp.inbox');
    Route::patch('whatsapp-inbox/{message}/read', [WhatsAppInboxController::class, 'markRead'])
        ->name('whatsapp.read');
});
