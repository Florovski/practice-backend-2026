<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    Route::get('/resources',            [ResourceController::class, 'index']);
    Route::get('/resources/{resource}', [ResourceController::class, 'show']);

    Route::middleware('admin')->group(function () {
        Route::post('/resources',              [ResourceController::class, 'store']);
        Route::put('/resources/{resource}',    [ResourceController::class, 'update']);
        Route::delete('/resources/{resource}', [ResourceController::class, 'destroy']);
    });

    // бронь
    Route::get('/bookings',          [BookingController::class, 'index']);
    Route::post('/bookings',         [BookingController::class, 'store']);
    Route::delete('/bookings/{id}',  [BookingController::class, 'destroy']);
});
