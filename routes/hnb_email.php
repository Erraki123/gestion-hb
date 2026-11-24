<?php
use App\Http\Controllers\HnbEmailController as Email;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','active','role:admin,commercial'])->prefix('email-commercial')->name('hnb.email.')->group(function(){
    Route::get('/',[Email::class,'index'])->name('index');
    Route::get('client/{client}',[Email::class,'show'])->whereNumber('client')->name('show');
    Route::post('client/{client}/draft',[Email::class,'draft'])->whereNumber('client')->middleware('throttle:6,1')->name('draft');
    Route::post('client/{client}/send',[Email::class,'send'])->whereNumber('client')->middleware('throttle:6,1')->name('send');
});
Route::middleware(['auth','active','role:admin'])->prefix('email-commercial')->name('hnb.email.')->group(function(){
    Route::get('pending',[Email::class,'pending'])->name('pending');
    Route::post('message/{message}/decision',[Email::class,'decide'])->whereNumber('message')->middleware('throttle:12,1')->name('decide');
});
