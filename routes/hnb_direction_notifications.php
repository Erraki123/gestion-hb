<?php

use App\Http\Controllers\Admin\HnbDirectionNotificationsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])->prefix('admin/direction/notifications')
    ->name('admin.direction.notifications.')->group(function () {
        Route::get('/', [HnbDirectionNotificationsController::class, 'index'])->name('index');
        Route::post('/seen', [HnbDirectionNotificationsController::class, 'seen'])
            ->middleware('throttle:30,1')->name('seen');
    });
