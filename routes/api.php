<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BillController;
use App\Http\Controllers\API\BillUploadBatchController;
use App\Http\Controllers\API\SocialiteController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VerifyBillController;
use App\Models\Category;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect']);
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback']);
Route::post('/auth/{provider}/callback', [SocialiteController::class, 'callback']); // Added POST for flexibility if frontend sends tokens

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user/{id}', [UserController::class, 'show']);

    Route::get('/bills/claimable-amount', [BillController::class, 'getClaimableAmount']);
    Route::apiResource('bills', BillController::class);
    Route::patch('/bills/{bill}/status', [BillController::class, 'changeStatus']);
    Route::get('/bills/{bill}/file', [BillController::class, 'viewFile'])->name('bills.file');
    Route::get('/batches/{batch}/preview', [BillUploadBatchController::class, 'preview'])->name('batches.preview');

    // Employee dashboard
    Route::get('/user/{id}/dashboard', [UserController::class, 'employeeDashboard']);
    Route::get('user/{id}/bills', [UserController::class, 'getUserBills']);
    Route::get('user/bill/{id}', [UserController::class, 'getUserBillsDetails']);

    // Admin dashboard
    Route::get('/employee/bills', [UserController::class, 'getEmployeeBills']);

    // Bill Submission (Normal User)
    Route::post('/batches/{batch}/submit', [BillController::class, 'submitBatch'])->name('batches.submit');

    // Admin Bill Verification
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::post('/bills/{bill}/verify', [VerifyBillController::class, 'verifyBill']);
        Route::post('/bills/{pivotId}/bulk-reimburse', [VerifyBillController::class, 'bulkReimburse']);
    });

    Route::get('/categories', function () {
        return response()->json([
            'data' => Category::where('is_active', true)->get(['id', 'name']),
        ]);
    });
});
