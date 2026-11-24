<?php

use App\Http\Controllers\HnbAgentRoutinesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'active', 'role:admin,commercial'])->group(function (): void {
    Route::get('/assistant/routines', [HnbAgentRoutinesController::class, 'index'])
        ->name('hnb.routines.index');
    Route::post('/assistant/routines/snooze', [HnbAgentRoutinesController::class, 'snooze'])
        ->middleware('throttle:20,1')->name('hnb.routines.snooze');
});
