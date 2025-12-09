<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VoucherController extends Controller
{
    // Cek Validitas Voucher
    public function check(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $voucher = Voucher::where('code', $request->code)->first();

        if (!$voucher) {
            return response()->json(['message' => 'Kode voucher tidak ditemukan.'], 404);
        }

        // Cek Kuota
        if ($voucher->stock <= 0) {
            return response()->json(['message' => 'Yah, voucher ini udah habis G.'], 400);
        }

        // Cek Tanggal
        $now = Carbon::now();
        if ($voucher->start_date && $now->lt($voucher->start_date)) {
            return response()->json(['message' => 'Voucher belum dimulai.'], 400);
        }
        if ($voucher->end_date && $now->gt($voucher->end_date)) {
            return response()->json(['message' => 'Voucher udah kadaluarsa.'], 400);
        }

        return response()->json([
            'message' => 'Voucher valid!',
            'data' => $voucher
        ]);
    }
}
