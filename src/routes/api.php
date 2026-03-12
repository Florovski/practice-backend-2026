<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);

Route::get('/resources',                          [ResourceController::class, 'index']);
Route::get('/resources/{resource}',               [ResourceController::class, 'show']);
Route::get('/resources/{resource}/schedule',      [ScheduleController::class, 'index']);
Route::get('/resources/{resource}/reviews',       [ReviewController::class, 'index']);

Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);
    
    Route::middleware('admin')->group(function () {
        Route::post('/resources',              [ResourceController::class, 'store']);
        Route::put('/resources/{resource}',    [ResourceController::class, 'update']);
        Route::delete('/resources/{resource}', [ResourceController::class, 'destroy']);
    });

    // бронь
    Route::get('/bookings',         [BookingController::class, 'index']);
    Route::post('/bookings',        [BookingController::class, 'store']);
    Route::delete('/bookings/{id}', [BookingController::class, 'destroy']);

    // отзывы
    Route::post('/resources/{resource}/reviews', [ReviewController::class, 'store']);
});
