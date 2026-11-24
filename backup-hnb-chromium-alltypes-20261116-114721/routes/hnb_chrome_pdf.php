<?php

use App\Http\Controllers\Admin\HnbChromePdfController;
use Illuminate\Support\Facades\Route;

// Studio preview for admin and owner commercial; downloads protected in controller.
Route::middleware(['web', 'auth', 'active', 'role:admin,commercial'])
    ->get('admin/pdf-studio/{type}/{id}', [HnbChromePdfController::class, 'show'])
    ->where(['type' => 'quote|invoice|delivery|receipt', 'id' => '[0-9]+'])
    ->name('admin.pdf-studio.show');
