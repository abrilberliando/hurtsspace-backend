<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\HeroSectionController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\VoucherController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\ShippingController;
use App\Http\Controllers\Api\LookbookController;
use App\Http\Controllers\Api\AdminOrderController;
use App\Http\Controllers\Api\AdminLookbookController;
use App\Http\Controllers\Api\AdminVoucherController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Middleware\IsAdmin;

/*
|--------------------------------------------------------------------------
| API Routes - HURTSSPACE (FIXED ROUTING ORDER)
|--------------------------------------------------------------------------
*/

// ========================================================================
// 🟢 1. PUBLIC ROUTES (Bebas Akses)
// ========================================================================

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// --- PRODUK & KATEGORI (URUTAN PENTING!) ---
Route::get('/products', [ProductController::class, 'index']);

// 🔥 [PENTING] "featured" HARUS DI ATAS "{id}"
// Kalau kebalik, "featured" bakal dianggap sebagai ID produk (404 Not Found)
Route::get('/products/featured', [ProductController::class, 'getFeatured']);

// Baru setelah itu rute ID (Wildcard)
Route::get('/products/{slug}', [ProductController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);

// Banner & Hero
Route::get('/banner/active', [BannerController::class, 'getActive']);
Route::get('/hero-section', [HeroSectionController::class, 'show']);

// Lookbook
Route::get('/lookbooks', [LookbookController::class, 'index']);
Route::get('/lookbooks/{id}', [LookbookController::class, 'show']);

// Webhook Midtrans
Route::post('/webhooks/midtrans', [WebhookController::class, 'handler']);


// ========================================================================
// 🟡 2. PROTECTED ROUTES (Login Member)
// ========================================================================
Route::middleware('auth:sanctum')->group(function () {

    // User & Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::put('/user', [AuthController::class, 'updateProfile']);

    // Cart & Checkout
    Route::post('/checkout', [CheckoutController::class, 'checkout']);

    // Shipping
    Route::get('/shipping/areas', [ShippingController::class, 'searchArea']);
    Route::post('/shipping/cost', [ShippingController::class, 'checkCost']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{invoice}', [OrderController::class, 'show']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
    Route::post('/orders/{id}/complete', [OrderController::class, 'complete']);

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);
    Route::get('/wishlist/check/{productId}', [WishlistController::class, 'check']);

    // Voucher
    Route::post('/vouchers/check', [VoucherController::class, 'check']);


    // ====================================================================
    // 🔴 3. ADMIN ONLY ROUTES (Middleware IsAdmin)
    // ====================================================================
    Route::middleware(IsAdmin::class)->group(function () {

        // Dashboard
        Route::get('/admin/dashboard', [AdminDashboardController::class, 'index']);

        // Hero Management
        Route::post('/admin/hero-section', [HeroSectionController::class, 'update']);

        // Products Management
        Route::post('/products', [ProductController::class, 'store']); // CREATE
        Route::apiResource('products', ProductController::class); // UPDATE
        Route::delete('/products/{id}', [ProductController::class, 'destroy']); // DELETE
        Route::put('/products/{id}/featured', [ProductController::class, 'setFeatured']); // SET FEATURED

        // Banners
        Route::get('/admin/banners', [BannerController::class, 'index']);
        Route::post('/admin/banners', [BannerController::class, 'store']);
        Route::delete('/admin/banners/{id}', [BannerController::class, 'destroy']);
        Route::put('/admin/banners/{id}/toggle', [BannerController::class, 'toggleActive']);

        // Orders
        Route::get('/admin/orders', [AdminOrderController::class, 'index']);
        Route::put('/admin/orders/{id}', [AdminOrderController::class, 'updateStatus']);

        // Lookbooks
        Route::get('/admin/lookbooks', [AdminLookbookController::class, 'index']);
        Route::post('/admin/lookbooks', [AdminLookbookController::class, 'store']);
        Route::delete('/admin/lookbooks/{id}', [AdminLookbookController::class, 'destroy']);

        // Vouchers
        Route::get('/admin/vouchers', [AdminVoucherController::class, 'index']);
        Route::post('/admin/vouchers', [AdminVoucherController::class, 'store']);
        Route::delete('/admin/vouchers/{id}', [AdminVoucherController::class, 'destroy']);

    });

});
