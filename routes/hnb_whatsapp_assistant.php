<?php

use App\Http\Controllers\HnbWhatsAppAssistantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','active','role:admin,commercial'])->prefix('whatsapp-assistant')->name('whatsapp.assistant.')->group(function () {
    Route::get('/', [HnbWhatsAppAssistantController::class,'index'])->name('index');
    Route::get('{message}/compose', [HnbWhatsAppAssistantController::class,'compose'])->whereNumber('message')->name('compose');
    Route::post('{message}/draft', [HnbWhatsAppAssistantController::class,'draft'])->whereNumber('message')->middleware('throttle:8,1')->name('draft');
});
