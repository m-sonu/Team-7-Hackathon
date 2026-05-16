<?php

use App\Http\Controllers\API\FileController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// routes/web.php
Route::middleware(['web'])->group(function () {
    Route::get('/bills/{bill}/preview', [FileController::class, 'preview'])->name('bills.file');
});
