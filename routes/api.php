<?php
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ResumeController;
use Illuminate\Http\Request;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/resumes/upload', [ResumeController::class, 'upload']);
    Route::get('/resumes', [ResumeController::class, 'list']);
    Route::get('/resumes/primary', [ResumeController::class, 'primary']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
