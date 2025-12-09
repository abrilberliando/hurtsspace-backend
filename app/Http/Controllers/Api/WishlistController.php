<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    // 1. LIHAT WISHLIST USER
    public function index(Request $request)
    {
        $wishlists = Wishlist::where('user_id', $request->user()->id)
            ->with([
                'product.images',
                'product.variants'
            ])
            ->latest()
            ->get();

        // Kita map biar strukturnya langsung jadi list produk
        $products = $wishlists->map(function ($item) {
            return $item->product;
        });

        return response()->json([
            'data' => $products
        ]);
    }

    // 2. TOGGLE WISHLIST (Add/Remove)
    public function toggle(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        $user = $request->user();
        $productId = $request->product_id;

        // Cek apakah udah ada di wishlist?
        $existing = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['message' => 'Removed from wishlist', 'status' => 'removed']);
        } else {
            Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $productId
            ]);
            return response()->json(['message' => 'Added to wishlist', 'status' => 'added']);
        }
    }

    // 3. CEK STATUS (Buat ngewarnain tombol love di frontend)
    public function check($productId)
    {
        $exists = Wishlist::where('user_id', auth()->id())
            ->where('product_id', $productId)
            ->exists();

        return response()->json(['is_wishlisted' => $exists]);
    }
}
