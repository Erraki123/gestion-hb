<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentReleaseApprovalController;

Route::middleware(['auth', 'active', 'role:admin,commercial', 'throttle:12,1'])->group(function () {
    Route::post('documents/{type}/{id}/release-request', [DocumentReleaseApprovalController::class, 'request'])
        ->where(['type' => 'quote|invoice', 'id' => '[0-9]+'])->name('documents.release.request');
});
Route::middleware(['auth', 'active', 'role:admin', 'throttle:12,1'])->group(function () {
    Route::post('documents/{type}/{id}/release-decision', [DocumentReleaseApprovalController::class, 'decide'])
        ->where(['type' => 'quote|invoice', 'id' => '[0-9]+'])->name('documents.release.decide');
});
