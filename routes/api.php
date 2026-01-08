<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return response()->json(['message' => 'Welcome to Venyo API']);
});

Route::prefix('v1')->group(function () {

    Route::get('/', function () {
        return response()->json(['message' => 'Welcome to Venyo V1 API']);
    });

    // Customer Routes
    Route::prefix('customer')->group(base_path('routes/api/v1/customer.php'));

    // Restaurant Routes
    Route::prefix('restaurant')->group(base_path('routes/api/v1/restaurant.php'));

    // Admin Routes
    Route::prefix('admin')->group(base_path('routes/api/v1/admin.php'));

});
