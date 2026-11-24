<?php

use App\Http\Controllers\CrmAiQuestionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','active','role:admin,commercial'])
    ->prefix('assistant-commercial/donnees')->name('commercial-ai.data.')->group(function () {
        Route::get('/', [CrmAiQuestionController::class,'index'])->name('index');
        Route::post('/', [CrmAiQuestionController::class,'ask'])->middleware('throttle:10,1')->name('ask');
    });
