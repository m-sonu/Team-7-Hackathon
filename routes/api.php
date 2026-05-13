<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BillController;
use App\Http\Controllers\API\BillUploadBatchController;
use App\Http\Controllers\API\SocialiteController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VerifyBillController;
use App\Http\Controllers\API\CategoryController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect']);
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback']);
Route::post('/auth/{provider}/callback', [SocialiteController::class, 'callback']); // Added POST for flexibility if frontend sends tokens

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user/show/{id}', [UserController::class, 'show']);

    Route::get('/bills/claimable-amount', [BillController::class, 'getClaimableAmount']);
    Route::apiResource('bills', BillController::class);
    Route::patch('/bills/{bill}/status', [BillController::class, 'changeStatus']);
    Route::get('/bills/{bill}/file', [BillController::class, 'viewFile'])->name('bills.file');
    Route::get('/batches/{batch}/preview', [BillUploadBatchController::class, 'preview'])->name('batches.preview');

   //Admin/Emplyee details only
    Route::middleware(['user.or.admin'])->group(function () {
        Route::get('user/{user}/bills', [UserController::class, 'getUserBills']);
        Route::get('user/bill/{id}', [UserController::class, 'getUserBillsDetails']);
    });

     // Employee dashboard
     Route::middleware(['employee'])->group(function () {
        Route::get('/user/{id}/dashboard', [UserController::class, 'employeeDashboard']);
        Route::post('/batches/{batch}/submit', [BillController::class, 'submitBatch'])->name('batches.submit');

    });

    // Admin Bill Verification
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::post('/bills/{bill}/verify', [VerifyBillController::class, 'verifyBill']);
        Route::post('/bills/{pivotId}/bulk-reimburse', [VerifyBillController::class, 'bulkReimburse']);
         Route::get('/employee/bills', [UserController::class, 'getEmployeeBills']);
         Route::get('/categoryWiseBills/{userId}/{categoryId}',[CategoryController::class,'getUserCategoryWiseBillDetails']);
        Route::get('/categoryWiseBills/{userId}',[CategoryController::class,'getUserCategoryWiseBills']);
    });

    //Category
    Route::get('/categories', [CategoryController::class, 'index']);
});
