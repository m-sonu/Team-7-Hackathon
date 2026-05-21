<?php

use App\Http\Controllers\API\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VerifyBillController;
use Illuminate\Support\Facades\Route;

Route::middleware('admin')->group(function () {
    Route::post('/bills/{bill}/verify', [VerifyBillController::class, 'verifyBill']);
    Route::post('/bills/bulk-reimburse', [VerifyBillController::class, 'bulkReimburse'])->name('bills.bulk-reimburse');
    Route::post('/bills/reimburse-by-pivot', [VerifyBillController::class, 'reimburseByPivot'])->name('bills.reimburse-by-pivot');
    Route::get('/employee/bills', [UserController::class, 'getEmployeeBills']);
    Route::get('/categoryWiseBills/{userId}/{categoryId}', [AdminCategoryController::class, 'getUserCategoryWiseBillDetails']);
    Route::get('/categoryWiseBills/{userId}', [AdminCategoryController::class, 'getUserCategoryWiseBills']);
    Route::post('/categories', [AdminCategoryController::class, 'store']);
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update']);
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy']);
});
