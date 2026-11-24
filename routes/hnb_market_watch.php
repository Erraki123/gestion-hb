<?php
use App\Http\Controllers\Admin\HnbMarketWatchController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','active','role:admin'])->prefix('admin/market-watch')->name('admin.market-watch.')->group(function () {
    Route::get('/',[HnbMarketWatchController::class,'index'])->name('index');
    Route::post('/search',[HnbMarketWatchController::class,'search'])->middleware('throttle:3,1')->name('search');
    Route::post('/observations',[HnbMarketWatchController::class,'store'])->middleware('throttle:12,1')->name('store');
});

Route::middleware(['auth','active','role:admin'])->prefix('admin/market-watch/research')->name('admin.market-research.')->group(function () {
    Route::get('/',[\App\Http\Controllers\Admin\HnbMarketResearchController::class,'index'])->name('index');
    Route::post('/preview',[\App\Http\Controllers\Admin\HnbMarketResearchController::class,'preview'])->middleware('throttle:2,1')->name('preview');
    Route::post('/report',[\App\Http\Controllers\Admin\HnbMarketResearchController::class,'report'])->middleware('throttle:4,1')->name('report');
});
