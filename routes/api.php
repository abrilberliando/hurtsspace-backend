<?php

use App\Http\Controllers\Api\AdminLinktreeController;
use App\Http\Controllers\Api\LinktreeController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\RunningTextController;
use App\Http\Controllers\Api\VideoBannerController;
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
use App\Http\Controllers\Api\NotificationController;
use App\Http\Middleware\IsAdmin;
use Illuminate\Routing\Middleware\ThrottleRequests;
use App\Http\Middleware\EnsureHttpsAndHsts;


/*
|--------------------------------------------------------------------------
| API Routes - HURTSSPACE (FIXED ROUTING ORDER)
|--------------------------------------------------------------------------
*/

// ========================================================================
// 🟢 1. PUBLIC ROUTES (Bebas Akses)
// ========================================================================

// Auth
Route::middleware([EnsureHttpsAndHsts::class, 'throttle:auth_public'])->group(function () {
    // Auth
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/auth/firebase-sync', [AuthController::class, 'firebaseSync']);
    Route::post('/auth/firebase-login', [AuthController::class, 'loginWithFirebase']);
    // Webhook Midtrans
    Route::post('/webhooks/midtrans', [WebhookController::class, 'handler']);
    // 👇 ROUTE VERIFIKASI EMAIL (Harus Public tapi Signed)
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->name('verification.verify');
    // 👇 ROUTE KIRIM ULANG VERIFIKASI (Sekarang Public via Email)
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification']);
    // RESET PASSWORD
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
});

// --- PRODUK & KATEGORI (FIXED ORDER) ---
// Rute spesifik/custom harus di atas rute wildcard ({slug})
Route::get('/products', [ProductController::class, 'index']);
Route::post('/products/validate-cart', [ProductController::class, 'validateCart']); // For frontend cart sync
Route::get('/products/featured', [ProductController::class, 'getFeatured']); // 🔥 Ditaruh di atas rute {slug}
Route::get('/products/{slug}', [ProductController::class, 'show']); // Wildcard untuk detail produk

Route::get('/categories', [CategoryController::class, 'index']);

Route::get('/running-text/active', [RunningTextController::class, 'getActive']);

// Banner & Hero & Video
Route::get('/banner/active', [BannerController::class, 'getActive']);
Route::get('/hero-section', [HeroSectionController::class, 'show']);
Route::get('/video-banner/active', [VideoBannerController::class, 'getActive']);

// Lookbook
Route::get('/lookbooks', [LookbookController::class, 'index']);
Route::get('/lookbooks/{id}', [LookbookController::class, 'show']);

// Linktree
Route::get('/linktree', [LinktreeController::class, 'index']);





// ========================================================================
// 🟡 2. PROTECTED ROUTES (Login Member)
// ========================================================================
Route::middleware(['auth:sanctum', EnsureHttpsAndHsts::class])->group(function () {

    Route::middleware(ThrottleRequests::class . ':auth_protected')->group(function () {
        // User & Profile
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
        // Note: ID di sini diasumsikan sebagai primary key ID Order, bukan invoice
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
        Route::post('/orders/{id}/complete', [OrderController::class, 'complete']);

        // Wishlist
        Route::get('/wishlist', [WishlistController::class, 'index']);
        Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);
        Route::get('/wishlist/check/{productId}', [WishlistController::class, 'check']);

        // Voucher
        Route::get('/vouchers', [VoucherController::class, 'index']); // 👈 Tambahin ini
        Route::post('/vouchers/check', [VoucherController::class, 'check']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::put('/notifications/read', [NotificationController::class, 'markAsRead']);
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    // ====================================================================
    // 🔴 3. ADMIN ONLY ROUTES (Middleware IsAdmin)
    // ====================================================================
    Route::middleware([IsAdmin::class, ThrottleRequests::class . ':auth_protected'])->prefix('admin')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        
        // Users Management
        Route::apiResource('users', \App\Http\Controllers\Api\AdminUserController::class)->except(['store']);
        Route::post('/users/{id}/restore', [\App\Http\Controllers\Api\AdminUserController::class, 'restore']);

        Route::get('products', [ProductController::class, 'index']);

        // Hero Management (FIXED: Karena Hero Section cuma 1 row)
        Route::post('/hero-section', [HeroSectionController::class, 'update']); // Menggunakan PUT untuk update

        // 💥 PRODUCTS MANAGEMENT (MENGGUNAKAN API RESOURCE EFEKTIF)
        // apiResource otomatis mencakup: index, show, store, update, destroy
        Route::apiResource('products', ProductController::class)->except(['index', 'show']);
        // Tambahkan rute kustom yang tidak tercakup apiResource
        Route::put('/products/{id}/featured', [ProductController::class, 'setFeatured']);

        // BANNERS (MENGGUNAKAN API RESOURCE EFEKTIF)
        // Rute untuk GET Index & Show disatukan di sini (Admin/Member/Guest tidak perlu rute show Banner)
        Route::apiResource('banners', BannerController::class)->except(['show', 'update']);
        // Video
        Route::apiResource('video-banners', VideoBannerController::class)->except(['show']);
        Route::put('/video-banners/{id}/toggle', [VideoBannerController::class, 'toggleActive']);
        // Tambahkan rute kustom
        Route::put('/banners/{id}/toggle', [BannerController::class, 'toggleActive']);
        // Change update route (because resource is 'banners')
        Route::put('/banners/{id}', [BannerController::class, 'update']);

        Route::apiResource('running-texts', RunningTextController::class)->except(['show']);
        Route::put('/running-texts/{id}/toggle', [RunningTextController::class, 'toggleActive']);

        // Categories (MANAGE KATEGORI) 👇
        Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);

        // Orders
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{id}', [AdminOrderController::class, 'show']); // Add show route
        Route::put('/orders/{id}', [AdminOrderController::class, 'updateStatus']);

        // Lookbooks (MENGGUNAKAN API RESOURCE EFEKTIF)
        Route::apiResource('lookbooks', AdminLookbookController::class)->except(['show', 'update']);
        Route::put('/lookbooks/{id}', [AdminLookbookController::class, 'update']); // Add update route

        // Vouchers (MENGGUNAKAN API RESOURCE EFEKTIF)
        Route::apiResource('vouchers', AdminVoucherController::class)->except(['show', 'update']);
        Route::put('/vouchers/{id}', [AdminVoucherController::class, 'update']); // Add update route
        Route::get('vouchers/{id}', [AdminVoucherController::class, 'show']);
        Route::post('vouchers/{id}/sync', [AdminVoucherController::class, 'syncProducts']);

        // Broadcast Notifikasi
        Route::post('/broadcast', [NotificationController::class, 'sendBroadcast']);

        // Linktree Management
        Route::get('/linktree', [AdminLinktreeController::class, 'getSettings']);
        Route::post('/linktree/settings', [AdminLinktreeController::class, 'updateSettings']); // POST for FormData (file upload)
        Route::post('/linktree/links', [AdminLinktreeController::class, 'storeLink']);
        Route::post('/linktree/links/{id}', [AdminLinktreeController::class, 'updateLink']); // POST for FormData (file upload)
        Route::delete('/linktree/links/{id}', [AdminLinktreeController::class, 'destroyLink']);
        Route::put('/linktree/links/{id}/toggle', [AdminLinktreeController::class, 'toggleLinkActive']);
        Route::put('/linktree/links/reorder', [AdminLinktreeController::class, 'reorderLinks']);
        
        // Backups
        Route::apiResource('backups', \App\Http\Controllers\Api\AdminBackupController::class)->only(['index', 'store', 'destroy']);
        Route::post('backups/{id}/restore', [\App\Http\Controllers\Api\AdminBackupController::class, 'restore']);
    });

});

