<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BillController;
use App\Http\Controllers\API\Employee\CategoryController as EmployeeCategoryController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user/show/{id}', [UserController::class, 'show']);

    Route::apiResource('bills', BillController::class);

    Route::middleware(['user.or.admin'])->group(function () {
        Route::get('user/{user}/bills', [UserController::class, 'getUserBills']);
        Route::get('user/bill/{id}', [UserController::class, 'getUserBillsDetails']);
    });

    // Accessible to all authenticated users
    Route::get('/categories', [EmployeeCategoryController::class, 'index']);

    Route::prefix('admin')
        ->namespace('\\')
        ->name('admin')
        ->group(function () {
            require base_path('routes/api/admin.php');
        });

    Route::prefix('employee')
        ->namespace('\\')
        ->name('employee')
        ->group(function () {
            require base_path('routes/api/employee.php');
        });
});
