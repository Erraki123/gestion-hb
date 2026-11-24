<?php
use App\Http\Controllers\Admin\HnbQuoteLearningController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth','active','role:admin','throttle:6,1'])->prefix('admin/quote-learning')->name('admin.quote-learning.')->group(function(){
    Route::get('/',[HnbQuoteLearningController::class,'index'])->name('index');
    Route::post('/collect',[HnbQuoteLearningController::class,'collect'])->name('collect');
    Route::post('/report',[HnbQuoteLearningController::class,'report'])->name('report');
});
