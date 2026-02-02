<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;

class AdminVoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::latest()->get();
        return response()->json(['data' => $vouchers]);
    }

    public function store(Request $request)
    {
        // 1. Update Validasi (Biar Field Baru Diterima Satpam Laravel)
        $request->validate([
            'code' => 'required|string|unique:vouchers,code|uppercase',
            'discount_type' => 'required|in:fixed,percent',
            'discount_amount' => 'required|numeric|min:1',
            'max_discount_amount' => 'nullable|numeric|min:0', // 👈 Field Baru
            'target' => 'required|in:products,shipping',      // 👈 Field Baru
            'is_all_products' => 'required|boolean',          // 👈 Field Baru
            'stock' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // 2. Simpan Data (Pake all() biar ringkes, tapi aman karena ada validasi & fillable)
        $voucher = Voucher::create([
            'code' => $request->code,
            'discount_type' => $request->discount_type,
            'discount_amount' => $request->discount_amount,
            'max_discount_amount' => $request->max_discount_amount, // 👈 Jangan lupa di-assign
            'target' => $request->target,                           // 👈 Jangan lupa di-assign
            'is_all_products' => $request->is_all_products,         // 👈 Jangan lupa di-assign
            'stock' => $request->stock,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json(['message' => 'Voucher berhasil dibuat!', 'data' => $voucher], 201);
    }

    public function destroy($id)
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->delete();
        return response()->json(['message' => 'Voucher dihapus.']);
    }

    public function show($id)
    {
        $voucher = Voucher::with('products:id,name')->findOrFail($id);
        return response()->json(['data' => $voucher]);
    }

    // 2. Simpan/Sync produk ke voucher
    public function syncProducts(Request $request, $id)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $voucher = Voucher::findOrFail($id);

        // Pake sync() biar Laravel otomatis hapus yang lama & nambah yang baru
        $voucher->products()->sync($request->product_ids);

        return response()->json(['message' => 'Produk berhasil di-update ke voucher!']);
    }
}
