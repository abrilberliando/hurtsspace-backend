<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VoucherController extends Controller
{
    // 👇 1. LIST VOUCHER AKTIF
    public function index()
    {
        $now = Carbon::now();

        $vouchers = Voucher::with('products:id,name') // 👈 Eager load biar tau produk mana aja yang dapet diskon
            ->where('stock', '>', 0)
            ->where(function ($query) use ($now) {
                $query->whereNull('start_date')
                      ->orWhere('start_date', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('end_date')
                      ->orWhere('end_date', '>=', $now);
            })
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Available vouchers retrieved',
            'data' => $vouchers
        ]);
    }

    // 👇 2. CEK VALIDITAS VOUCHER
    public function check(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        // Load relasi produk buat jaga-jaga kalau vouchernya cuma buat barang tertentu
        $voucher = Voucher::with('products:id,name')->where('code', $request->code)->first();

        if (!$voucher) {
            return response()->json(['message' => 'Voucher code not found.'], 404);
        }

        // --- VALIDASI STANDAR ---
        if ($voucher->stock <= 0) {
            return response()->json(['message' => 'Yah, voucher ini udah habis G.'], 400);
        }

        $now = Carbon::now();
        if ($voucher->start_date && $now->lt($voucher->start_date)) {
            return response()->json(['message' => 'Voucher is not yet active.'], 400);
        }
        if ($voucher->end_date && $now->gt($voucher->end_date)) {
            return response()->json(['message' => 'Voucher has expired.'], 400);
        }

        // --- TAMBAHAN INFO INFO ---
        // Kita kirim info target-nya ke frontend biar Next.js lo bisa ngitung
        return response()->json([
            'message' => 'Voucher is valid!',
            'data' => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'discount_amount' => (int) $voucher->discount_amount,
                'discount_type' => $voucher->discount_type,
                'max_discount_amount' => (int) $voucher->max_discount_amount,
                'target' => $voucher->target, // 'products' atau 'shipping'
                'is_all_products' => $voucher->is_all_products,
                'applicable_products' => $voucher->products, // List produk kalau is_all_products false
            ]
        ]);
    }
}
