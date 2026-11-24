<?php
use App\Http\Controllers\Admin\DiscoveryReviewController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','active','role:admin'])->prefix('admin/discovery')->name('admin.discovery.')->group(function(){
    Route::post('{candidate}/accept',[DiscoveryReviewController::class,'accept'])->name('accept');
    Route::post('{candidate}/dismiss',[DiscoveryReviewController::class,'dismiss'])->name('dismiss');
});

// HNB ATLAS 5B: search is public-source-only; persistence is admin-only.
Route::middleware(['auth','active','role:admin,commercial'])->prefix('prospects/atlas')->name('prospects.atlas.')->group(function () {
    Route::get('/', [\App\Http\Controllers\HnbAtlasSearchController::class, 'index'])->name('index');
    Route::post('/search', [\App\Http\Controllers\HnbAtlasSearchController::class, 'search'])->middleware('throttle:3,1')->name('search');
});

// HNB Phase 6A - admin-only market evidence.
require __DIR__.'/hnb_market_watch.php';
