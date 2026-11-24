<?php
use App\Http\Controllers\HnbWhatsAppConversationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','active','role:admin,commercial'])->prefix('whatsapp-assistant')->name('whatsapp.assistant.')->group(function () {
    Route::get('{message}/conversation',[HnbWhatsAppConversationController::class,'show'])->whereNumber('message')->name('conversation');
    Route::post('{message}/send',[HnbWhatsAppConversationController::class,'send'])->whereNumber('message')->middleware('throttle:6,1')->name('send');
});
Route::middleware(['auth','active','role:admin'])->prefix('whatsapp-assistant')->name('whatsapp.assistant.')->group(function () {
    Route::get('pending',[HnbWhatsAppConversationController::class,'pending'])->name('pending');
    Route::post('outbound/{outbound}/decision',[HnbWhatsAppConversationController::class,'decide'])->whereNumber('outbound')->middleware('throttle:12,1')->name('decide');
});
