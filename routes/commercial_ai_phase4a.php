<?php

use App\Http\Controllers\CommercialAiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin,commercial'])->prefix('assistant-commercial')->name('commercial-ai.')->group(function () {
    Route::get('/', [CommercialAiController::class, 'index'])->name('index');
    Route::post('/generer', [CommercialAiController::class, 'generate'])->middleware('throttle:6,1')->name('generate');
});
