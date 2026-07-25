<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/test', fn () => response()->json(['message' => 'Laravel API is working']));

// ----- Public Authentication Routes -----
Route::post('/register/consumer', [AuthController::class, 'registerConsumer']);
Route::post('/register/vendor', [AuthController::class, 'registerVendor']);
Route::post('/login', [AuthController::class, 'login']);

// ----- Public OTP Routes -----
Route::post('/otp/verify', [AuthController::class, 'verifyOtp']);
Route::post('/otp/resend', [AuthController::class, 'resendOtp']);

// ----- Public Forgot Password Routes -----
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);

// ----- Authenticated Routes (Sanctum token required) -----
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // ----- Admin Routes -----
    Route::middleware('role:Admin')->prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);

        // Vendor Approvals
        Route::get('/vendors/pending', [AdminController::class, 'pendingVendors']);
        Route::post('/vendors/{storeId}/approve', [AdminController::class, 'approveVendor']);
        Route::post('/vendors/{storeId}/reject', [AdminController::class, 'rejectVendor']);

        // Manage Vendors
        Route::get('/vendors', [AdminController::class, 'listVendors']);
        Route::patch('/vendors/{userId}/status', [AdminController::class, 'updateVendorStatus']);

        // Manage Consumers
        Route::get('/consumers', [AdminController::class, 'listConsumers']);
        Route::delete('/consumers/{userId}', [AdminController::class, 'deleteConsumer']);
    });

    // ----- Vendor Routes -----
    Route::middleware('role:Vendor')->prefix('vendor')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index']);
        Route::post('/inventory', [InventoryController::class, 'store']);
    });

    // ----- Consumer Routes -----
    Route::middleware('role:Consumer')->prefix('consumer')->group(function () {
        Route::get('/home', fn () => response()->json(['message' => 'Welcome to the Consumer Home']));
    });
});