<?php

use App\Http\Controllers\Admin\AiFloatingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin,commercial'])
    ->post('/admin/ai-floating/ask', [AiFloatingController::class, 'ask'])
    ->middleware('throttle:8,1')
    ->name('admin.ai-floating.ask');
