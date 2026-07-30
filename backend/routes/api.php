<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check
Route::get('/test', fn () => response()->json(['message' => 'Laravel API is working']));

// ----- Public Application Data Routes -----
Route::get('/categories', [CategoryController::class, 'index']);

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
    // ----- Global Category Routes -----
    Route::post('/categories', [CategoryController::class, 'store']);

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
        Route::delete('/vendors/{userId}', [AdminController::class, 'deleteVendor']);

        // Manage Consumers
        Route::get('/consumers', [AdminController::class, 'listConsumers']);
        Route::patch('/consumers/{userId}/status', [AdminController::class, 'updateConsumerStatus']);
        Route::delete('/consumers/{userId}', [AdminController::class, 'deleteConsumer']);
    });

    // ----- Vendor Routes -----
    Route::middleware('role:Vendor')->prefix('vendor')->group(function () {
        Route::get('/products', [InventoryController::class, 'index']);
        Route::post('/products', [InventoryController::class, 'store']);
        Route::get('/products/categories', [InventoryController::class, 'categories']);
        Route::get('/stats', [\App\Http\Controllers\VendorController::class, 'stats']);
        Route::get('/profile', [\App\Http\Controllers\VendorController::class, 'profile']);
        Route::post('/profile/hours', [ProfileController::class, 'updateStoreHours']);
        
        Route::get('/sales/metrics', [\App\Http\Controllers\SalesController::class, 'metrics']);
        Route::get('/sales/transactions', [\App\Http\Controllers\SalesController::class, 'transactions']);
        Route::post('/sales/manual', [\App\Http\Controllers\SalesController::class, 'storeManual']);
        
        Route::get('/orders', [\App\Http\Controllers\OrderController::class, 'index']);
        Route::get('/orders/{id}', [\App\Http\Controllers\OrderController::class, 'show']);
        Route::get('/customers', [\App\Http\Controllers\OrderController::class, 'customers']);
        Route::get('/customers/{id}/orders', [\App\Http\Controllers\OrderController::class, 'customerOrders']);
    });

    // ----- Profile Routes -----
    Route::middleware('role:Consumer')->prefix('profile')->group(function () {
        Route::post('/photo', [ProfileController::class, 'updatePhoto']);
        Route::post('/personal-info', [ProfileController::class, 'updatePersonalInfo']);
        Route::post('/phone-request-otp', [ProfileController::class, 'requestPhoneOtp']);
        Route::post('/phone-verify-otp', [ProfileController::class, 'verifyPhoneOtp']);
        Route::post('/email', [ProfileController::class, 'updateEmail']);
        Route::post('/password', [ProfileController::class, 'updatePassword']);
        Route::delete('/delete', [ProfileController::class, 'deleteAccount']);
    });

    // ----- Consumer Routes -----
    Route::middleware('role:Consumer')->prefix('consumer')->group(function () {
        Route::get('/home', fn () => response()->json(['message' => 'Welcome to the Consumer Home']));
    });
});