<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Middleware\IsAdmin;

// ========================================================================
// 🟢 1. PUBLIC ROUTES (Bebas Akses)
// ========================================================================

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Katalog Produk
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);


// ========================================================================
// 🟡 2. PROTECTED ROUTES (Login User/Member)
// ========================================================================
Route::middleware('auth:sanctum')->group(function () {
    // Auth Actions
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    // Fitur Shipping
    Route::get('/shipping/areas', [\App\Http\Controllers\Api\ShippingController::class, 'searchArea']);
    Route::post('/shipping/cost', [\App\Http\Controllers\Api\ShippingController::class, 'checkCost']);

    // ====================================================================
    // 🔴 3. ADMIN ONLY ROUTES (Area Terlarang buat Member)
    // ====================================================================
    Route::middleware(IsAdmin::class)->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    });

});
