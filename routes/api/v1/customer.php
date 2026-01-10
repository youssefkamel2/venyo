<?php

use App\Http\Controllers\Api\V1\Customer\AuthController;
use App\Http\Controllers\Api\V1\Customer\RestaurantController;
use App\Http\Controllers\Api\V1\Customer\ReservationController;
use App\Http\Controllers\Api\V1\Customer\ReviewController;
use App\Http\Controllers\Api\V1\Customer\FavoriteController;
use App\Http\Controllers\Api\V1\Customer\ProfileController;
use App\Http\Controllers\Api\V1\PublicLookupController;
use App\Http\Controllers\Api\V1\Customer\ContactController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer API Routes
|--------------------------------------------------------------------------
*/

// Public Lookups
Route::get('areas', [PublicLookupController::class, 'areas']);
Route::get('cuisines', [PublicLookupController::class, 'cuisines']);
Route::get('types', [PublicLookupController::class, 'types']);

// Public Auth Routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('password/verify-code', [AuthController::class, 'verifyResetCode']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

// contact us
Route::post('contact', [ContactController::class, 'store']);

// Restaurant Browsing (Public)
Route::prefix('restaurants')->group(function () {
    Route::get('/', [RestaurantController::class, 'index']);
    Route::get('/promoted', [RestaurantController::class, 'promoted']);
    Route::get('/{slug}', [RestaurantController::class, 'show']);
    Route::get('/{slug}/available-slots', [RestaurantController::class, 'availableSlots']);
});

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('email/verify-code', [AuthController::class, 'verifyEmailCode']);
    Route::post('email/resend-code', [AuthController::class, 'resendVerificationCode']);

    // Reservations
    Route::prefix('reservations')->group(function () {
        Route::get('/', [ReservationController::class, 'index']);
        Route::get('/{id}', [ReservationController::class, 'show']);
        Route::post('/lock-slot', [ReservationController::class, 'lockSlot']);
        Route::post('/', [ReservationController::class, 'store']);
        Route::post('/{id}/cancel', [ReservationController::class, 'cancel']);
        Route::get('/{id}/menu', [ReservationController::class, 'getMenu']);
        Route::post('/{id}/pre-order', [ReservationController::class, 'submitPreOrder']);
    });

    // Reviews
    Route::prefix('reviews')->group(function () {
        Route::get('/', [ReviewController::class, 'index']);
        Route::post('/', [ReviewController::class, 'store']);
    });

    // Favorites
    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index']);
        Route::post('/toggle', [FavoriteController::class, 'toggle']);
    });

    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('/password', [ProfileController::class, 'updatePassword']);
        Route::put('/locale', [ProfileController::class, 'updateLocale']);

        // New Profile Features
        Route::post('/avatar', [ProfileController::class, 'updateAvatar']);
        Route::delete('/avatar', [ProfileController::class, 'deleteAvatar']);
        Route::post('/dates', [ProfileController::class, 'storeDate']);
        Route::delete('/dates/{id}', [ProfileController::class, 'deleteDate']);
    });

});
