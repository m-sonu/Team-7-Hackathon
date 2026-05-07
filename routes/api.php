<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\API\SocialiteController;
use App\Http\Controllers\Api\UserController;
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
    Route::get('/bills/{bill}/file', [BillController::class, 'viewFile']);

    Route::get('/user/{id}', [UserController::class, 'show']);
    Route::get('user/{id}/bills', [UserController::class , 'getUserBills']);
    Route::get('user/bill/{id}', [UserController::class , 'getUserBillsDetails']);
});
