<?php

use App\Http\Controllers\CrmAiUnifiedController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','active','role:admin,commercial'])
    ->prefix('assistant-commercial/rapide')->name('commercial-ai.quick.')->group(function () {
        Route::get('/', [CrmAiUnifiedController::class, 'index'])->name('index');
        Route::post('/', [CrmAiUnifiedController::class, 'ask'])->middleware('throttle:8,1')->name('ask');
    });
