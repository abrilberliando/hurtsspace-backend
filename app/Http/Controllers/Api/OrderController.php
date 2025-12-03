<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    // 1. LIHAT RIWAYAT BELANJA (My Orders)
    public function index(Request $request)
    {
        // Ambil order milik user yang sedang login
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items.product', 'items.variant']) // Load detail barang
            ->latest() // Urutkan dari yang terbaru
            ->get();

        return response()->json([
            'message' => 'Order history retrieved',
            'data' => $orders
        ]);
    }

    // 2. LIHAT DETAIL SATU ORDER (Opsional, buat tombol "View Detail")
    public function show($invoice)
    {
        $order = Order::where('invoice_number', $invoice)
            ->where('user_id', auth()->id()) // Pastikan punya dia sendiri
            ->with(['items.product', 'items.variant'])
            ->firstOrFail();

        return response()->json([
            'message' => 'Order detail retrieved',
            'data' => $order
        ]);
    }
    // 3. BATALKAN PESANAN (Cancel Order)
    public function cancel($id)
    {
        // Cari order punya user yang statusnya masih pending
        $order = Order::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'pending') // Cuma boleh batalin yang pending
            ->with('items')
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan atau tidak bisa dibatalkan.'], 404);
        }

        // Balikin Stok ke Gudang (Restock)
        foreach ($order->items as $item) {
            $variant = \App\Models\ProductVariant::find($item->product_variant_id);
            if ($variant) {
                $variant->increment('stock', $item->quantity);
            }
        }

        // Ubah status jadi cancelled
        $order->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Order berhasil dibatalkan. Stok telah dikembalikan.']);
    }
}
