<?php

use App\Http\Controllers\Admin\AtlasScoreV2Controller;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'active', 'role:admin', 'throttle:60,1'])
    ->get('/admin/atlas/scoring-v2', [AtlasScoreV2Controller::class, 'index'])
    ->name('admin.atlas.scoring-v2');
