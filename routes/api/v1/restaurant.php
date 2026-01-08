<?php

use App\Http\Controllers\Api\V1\Restaurant\AuthController;
use App\Http\Controllers\Api\V1\Restaurant\ProfileController;
use App\Http\Controllers\Api\V1\Restaurant\TimeSlotController;
use App\Http\Controllers\Api\V1\Restaurant\ReservationController;
use App\Http\Controllers\Api\V1\Restaurant\PhotoController;
use App\Http\Controllers\Api\V1\Restaurant\SubscriptionController;
use App\Http\Controllers\Api\V1\Restaurant\StatisticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Restaurant API Routes
|--------------------------------------------------------------------------
*/

// Public Auth Routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    
    // Statistics
    Route::get('statistics/summary', [StatisticsController::class, 'index']);

    // Profile
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    
    // Photos
    Route::prefix('photos')->group(function () {
        Route::post('/', [PhotoController::class, 'store']);
        Route::delete('/{id}', [PhotoController::class, 'destroy']);
        Route::post('/{id}/set-cover', [PhotoController::class, 'setCover']);
    });
    
    // Time Slots
    Route::prefix('slots')->group(function () {
        Route::get('/', [TimeSlotController::class, 'index']);
        Route::post('/', [TimeSlotController::class, 'store']);
        Route::put('/{id}', [TimeSlotController::class, 'update']);
        Route::delete('/{id}', [TimeSlotController::class, 'destroy']);
    });

    // Reservations
    Route::prefix('reservations')->group(function () {
        Route::get('/', [ReservationController::class, 'index']);
        Route::put('/{id}/status', [ReservationController::class, 'updateStatus']);
    });

    // Subscriptions & Plans
    Route::get('plans', [SubscriptionController::class, 'plans']);
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index']);
        Route::post('/', [SubscriptionController::class, 'subscribe']);
        Route::get('/invoices', [SubscriptionController::class, 'invoices']);
    });
    
});
