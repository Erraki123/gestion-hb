<?php

use App\Http\Controllers\Admin\HnbChromePdfController;
use Illuminate\Support\Facades\Route;

// Isolated admin-only preview; does not bypass document approvals or replace existing PDFs.
Route::middleware(['web', 'auth', 'active', 'role:admin'])
    ->get('admin/pdf-studio/{type}/{id}', [HnbChromePdfController::class, 'show'])
    ->where(['type' => 'quote|invoice|delivery|receipt', 'id' => '[0-9]+'])
    ->name('admin.pdf-studio.show');
