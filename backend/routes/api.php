<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Public routes: registration, login, health check
| Authenticated routes: logout, user profile, role-protected endpoints
|
*/

// Health check
Route::get('/test', function () {
    return response()->json([
        'message' => 'Laravel API is working'
    ]);
});

// ----- Public Authentication Routes -----
Route::post('/register/consumer', [AuthController::class, 'registerConsumer']);
Route::post('/register/vendor', [AuthController::class, 'registerVendor']);
Route::post('/login', [AuthController::class, 'login']);

// ----- Authenticated Routes (Sanctum token required) -----
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Admin-only routes
    Route::middleware('role:Admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', fn () => response()->json([
            'message' => 'Welcome to the Admin Dashboard',
        ]));
    });

    // Vendor-only routes
    Route::middleware('role:Vendor')->prefix('vendor')->group(function () {
        Route::get('/dashboard', fn () => response()->json([
            'message' => 'Welcome to the Vendor Dashboard',
        ]));
    });

    // Consumer-only routes
    Route::middleware('role:Consumer')->prefix('consumer')->group(function () {
        Route::get('/home', fn () => response()->json([
            'message' => 'Welcome to the Consumer Home',
        ]));
    });
});