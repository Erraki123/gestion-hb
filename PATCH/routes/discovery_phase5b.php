<?php
use App\Http\Controllers\Admin\DiscoveryReviewController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','active','role:admin'])->prefix('admin/discovery')->name('admin.discovery.')->group(function(){
    Route::post('{candidate}/accept',[DiscoveryReviewController::class,'accept'])->name('accept');
    Route::post('{candidate}/dismiss',[DiscoveryReviewController::class,'dismiss'])->name('dismiss');
});
