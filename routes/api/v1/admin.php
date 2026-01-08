<?php

use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Admin\RestaurantController;
use App\Http\Controllers\Api\V1\Admin\LookupController;
use App\Http\Controllers\Api\V1\Admin\PlanController;
use App\Http\Controllers\Api\V1\Admin\StatisticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
*/

// Public Auth Routes
Route::post('login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    
    // Dashboard Stats
    Route::get('statistics/summary', [StatisticsController::class, 'summary']);

    // User Management
    Route::apiResource('users', UserController::class);
    
    // Restaurant & Owner Management
    Route::apiResource('restaurants', RestaurantController::class);
    
    // Lookups Management
    Route::prefix('lookups')->group(function () {
        Route::get('areas', [LookupController::class, 'areasIndex']);
        Route::post('areas', [LookupController::class, 'areaStore']);
        Route::get('cuisines', [LookupController::class, 'cuisinesIndex']);
        Route::post('cuisines', [LookupController::class, 'cuisineStore']);
    });

    // Plans Management
    Route::apiResource('plans', PlanController::class);
});
