<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Middleware\IsAdmin;
use App\Http\Controllers\Api\BannerController;

// ========================================================================
// 🟢 1. PUBLIC ROUTES (Bebas Akses)
// ========================================================================

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Katalog Produk
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

// Webhook Midtrans
Route::post('/webhooks/midtrans', [\App\Http\Controllers\Api\WebhookController::class, 'handler']);

// LookBook
Route::get('/lookbooks', [\App\Http\Controllers\Api\LookbookController::class, 'index']);
Route::get('/lookbooks/{id}', [\App\Http\Controllers\Api\LookbookController::class, 'show']);

// Banners
Route::get('/banners/active', [BannerController::class, 'getActive']);


// ========================================================================
// 🟡 2. PROTECTED ROUTES (Login User/Member)
// ========================================================================
Route::middleware('auth:sanctum')->group(function () {
    // Auth Actions
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/user', [AuthController::class, 'updateProfile']);
    // Fitur Shipping
    Route::get('/shipping/areas', [\App\Http\Controllers\Api\ShippingController::class, 'searchArea']);
    Route::post('/shipping/cost', [\App\Http\Controllers\Api\ShippingController::class, 'checkCost']);
    // Fitur Payment
    Route::post('/checkout', [\App\Http\Controllers\Api\CheckoutController::class, 'checkout']);
    // Order
    Route::get('/orders', [\App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::get('/orders/{invoice}', [\App\Http\Controllers\Api\OrderController::class, 'show']);
    Route::post('/orders/{id}/cancel', [\App\Http\Controllers\Api\OrderController::class, 'cancel']);
    // DASHBOARD STATS
    Route::get('/admin/dashboard', [\App\Http\Controllers\Api\AdminDashboardController::class, 'index']);
    // Category
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
    // WISHLIST
    Route::get('/wishlist', [\App\Http\Controllers\Api\WishlistController::class, 'index']);
    Route::post('/wishlist/toggle', [\App\Http\Controllers\Api\WishlistController::class, 'toggle']);
    Route::get('/wishlist/check/{productId}', [\App\Http\Controllers\Api\WishlistController::class, 'check']);
    // Voucher
    Route::post('/vouchers/check', [\App\Http\Controllers\Api\VoucherController::class, 'check']);
    // ====================================================================
    // 🔴 3. ADMIN ONLY ROUTES (Area Terlarang buat Member)
    // ====================================================================
    Route::middleware(IsAdmin::class)->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);

        // 👇 TAMBAHAN BARU: MANAGE ORDERS
        Route::get('/admin/orders', [\App\Http\Controllers\Api\AdminOrderController::class, 'index']);
        Route::put('/admin/orders/{id}', [\App\Http\Controllers\Api\AdminOrderController::class, 'updateStatus']);

        // LOOKBOOK MANAGEMENT
        Route::get('/admin/lookbooks', [\App\Http\Controllers\Api\AdminLookbookController::class, 'index']);
        Route::post('/admin/lookbooks', [\App\Http\Controllers\Api\AdminLookbookController::class, 'store']);
        Route::delete('/admin/lookbooks/{id}', [\App\Http\Controllers\Api\AdminLookbookController::class, 'destroy']);

        // VOUCHER MANAGEMENT
        Route::get('/admin/vouchers', [\App\Http\Controllers\Api\AdminVoucherController::class, 'index']);
        Route::post('/admin/vouchers', [\App\Http\Controllers\Api\AdminVoucherController::class, 'store']);
        Route::delete('/admin/vouchers/{id}', [\App\Http\Controllers\Api\AdminVoucherController::class, 'destroy']);

        // Banners Management
        Route::get('admin/banners', [BannerController::class, 'index']);
        Route::post('admin/banners', [BannerController::class, 'store']);
        Route::patch('admin/banners/{id}/toggle', [BannerController::class, 'toggleActive']);
        Route::delete('admin/banners/{id}', [BannerController::class, 'destroy']);

    });

});
