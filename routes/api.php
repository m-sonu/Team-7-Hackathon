<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\API\SocialiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect']);
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback']);
Route::post('/auth/{provider}/callback', [SocialiteController::class, 'callback']); // Added POST for flexibility if frontend sends tokens

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/bills/claimable-amount', [BillController::class, 'getClaimableAmount']);
    Route::patch('/admin/claim-status', [BillController::class, 'bulkUpdateClaimStatus']);
    Route::apiResource('bills', BillController::class);
    Route::patch('/bills/{bill}/status', [BillController::class, 'changeStatus']);
});
