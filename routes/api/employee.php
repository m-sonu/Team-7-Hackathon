<?php

use App\Http\Controllers\API\BillUploadBatchController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('employee')->group(function () {
    Route::get('/{id}/dashboard', [UserController::class, 'employeeDashboard']);
    Route::group(['prefix' => 'batches/{batch}'], function () {
        Route::get('preview', [BillUploadBatchController::class, 'preview'])->name('batches.preview');
        Route::post('/submit', [BillUploadBatchController::class, 'submitBatch'])->name('batches.submit');
    });

    Route::get('/bill/{id}', [UserController::class, 'getUserBillsDetails']);
});
