<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;

class AdminVoucherController extends Controller
{
    // 1. List Semua Voucher
    public function index()
    {
        $vouchers = Voucher::latest()->get();
        return response()->json(['data' => $vouchers]);
    }

    // 2. Bikin Voucher Baru
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:vouchers,code|uppercase', // Kode harus unik & kapital
            'discount_type' => 'required|in:fixed,percent',
            'discount_amount' => 'required|numeric|min:1',
            'stock' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $voucher = Voucher::create([
            'code' => $request->code,
            'discount_type' => $request->discount_type,
            'discount_amount' => $request->discount_amount,
            'stock' => $request->stock,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json(['message' => 'Voucher berhasil dibuat!', 'data' => $voucher], 201);
    }

    // 3. Hapus Voucher
    public function destroy($id)
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->delete();
        return response()->json(['message' => 'Voucher dihapus.']);
    }
}
