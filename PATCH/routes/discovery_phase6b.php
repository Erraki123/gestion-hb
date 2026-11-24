<?php

use App\Http\Controllers\Admin\DiscoveryCsvUploadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'active', 'role:admin', 'throttle:6,1'])
    ->post('/admin/discovery/upload-csv', [DiscoveryCsvUploadController::class, 'store'])
    ->name('admin.discovery.upload-csv');
