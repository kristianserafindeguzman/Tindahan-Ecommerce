<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\StoreController;
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
Route::get('/products', [CatalogController::class, 'products']);
Route::get('/stores', [StoreController::class, 'index']);

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
    Route::get('/user', function (\Illuminate\Http\Request $request) {
        \Log::info('API USER route hit');
        return app(\App\Http\Controllers\AuthController::class)->user($request);
    });
    // ----- Global Category Routes -----
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::patch('/categories/{id}', [CategoryController::class, 'update']);

    // ----- Admin Routes -----
    Route::middleware('role:Admin')->prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);

        // Vendor Approvals
        Route::get('/vendors/pending', [AdminController::class, 'pendingVendors']);
        Route::get('/vendors/pending/export', [AdminController::class, 'exportPendingVendors']);
        Route::post('/vendors/{storeId}/approve', [AdminController::class, 'approveVendor']);
        Route::post('/vendors/{storeId}/reject', [AdminController::class, 'rejectVendor']);

        // Vendors Management
        Route::get('/vendors', [AdminController::class, 'listVendors']);
        Route::get('/vendors/export', [AdminController::class, 'exportVendors']);
        Route::patch('/vendors/{userId}/status', [AdminController::class, 'updateVendorStatus']);
        Route::delete('/vendors/{userId}', [AdminController::class, 'deleteVendor']);

        // Consumers Management
        Route::get('/consumers', [AdminController::class, 'listConsumers']);
        Route::get('/consumers/export', [AdminController::class, 'exportConsumers']);
        Route::patch('/consumers/{userId}/status', [AdminController::class, 'updateConsumerStatus']);
        Route::delete('/consumers/{userId}', [AdminController::class, 'deleteConsumer']);
    });

    // ----- Vendor Routes -----
    Route::middleware('role:Vendor')->prefix('vendor')->group(function () {
        Route::get('/products', [InventoryController::class, 'index']);
        Route::post('/products', [InventoryController::class, 'store']);
        Route::patch('/products/{id}', [InventoryController::class, 'update']);
        Route::delete('/products/{id}', [InventoryController::class, 'destroy']);
        Route::get('/products/categories', [InventoryController::class, 'categories']);
        Route::get('/categories/export', [\App\Http\Controllers\VendorController::class, 'exportProductCategoryReport']);
        Route::get('/stats', [\App\Http\Controllers\VendorController::class, 'stats']);
        Route::get('/stats/chart', [\App\Http\Controllers\VendorController::class, 'chartStats']);
        Route::get('/inventory/export', [\App\Http\Controllers\VendorController::class, 'exportInventoryReport']);
        Route::get('/inventory/export-html', [\App\Http\Controllers\VendorController::class, 'exportInventoryReportHtml']);
        Route::get('/profile', [\App\Http\Controllers\VendorController::class, 'profile']);
        Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'updatePersonalInfo']);
        Route::put('/profile/hours', [\App\Http\Controllers\ProfileController::class, 'updateStoreHours']);
        Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword']);
        Route::put('/store/info', [\App\Http\Controllers\ProfileController::class, 'updateStoreInfo']);
        Route::put('/store/address', [\App\Http\Controllers\ProfileController::class, 'updateStoreAddress']);
        Route::post('/profile/store-image', [\App\Http\Controllers\VendorController::class, 'uploadStoreImage']);
        Route::delete('/account', [\App\Http\Controllers\ProfileController::class, 'deleteAccount']);
        
        Route::get('/sales/metrics', [\App\Http\Controllers\SalesController::class, 'metrics']);
        Route::get('/sales/transactions', [\App\Http\Controllers\SalesController::class, 'transactions']);
        Route::post('/sales/manual', [\App\Http\Controllers\SalesController::class, 'storeManual']);
        
        Route::get('/orders', [\App\Http\Controllers\VendorOrderController::class, 'index']);
        Route::get('/orders/export', [\App\Http\Controllers\VendorController::class, 'exportOrderListReport']);
        Route::get('/orders/{id}', [\App\Http\Controllers\VendorOrderController::class, 'show']);
        Route::get('/orders/{id}/export', [\App\Http\Controllers\VendorController::class, 'exportCustomerOrderReport']);
        Route::patch('/orders/{id}/status', [\App\Http\Controllers\VendorOrderController::class, 'updateStatus']);
        Route::get('/customers', [\App\Http\Controllers\VendorOrderController::class, 'customers']);
        Route::get('/customers/{id}/orders', [\App\Http\Controllers\VendorOrderController::class, 'customerOrders']);
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

        // Cart
        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart', [CartController::class, 'store']);
        Route::patch('/cart/{id}', [CartController::class, 'update']);
        Route::delete('/cart/{id}', [CartController::class, 'destroy']);

        // Checkout
        Route::post('/checkout', [CartController::class, 'checkout']);

        // Consumer Orders
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::patch('/orders/{id}/cancel', [OrderController::class, 'cancel']);

        // Consumer Notifications
        Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
        Route::patch('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
    });
});