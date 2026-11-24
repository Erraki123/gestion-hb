<?php
use App\Http\Controllers\Admin\AtlasContactDecisionController;
use Illuminate\Support\Facades\Route;
Route::middleware(['web','auth','active','role:admin','throttle:20,1'])
    ->post('admin/atlas/contacts/{candidate}/decision', [AtlasContactDecisionController::class, 'store'])
    ->name('admin.atlas.contacts.decision');
