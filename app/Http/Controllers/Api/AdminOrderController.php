<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class AdminOrderController extends Controller
{
    // 1. LIHAT SEMUA ORDER (Admin View)
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product', 'items.variant'])->latest();

        // Filter by Status (Optional)
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(20); // Paginasi 20 per halaman

        return response()->json([
            'message' => 'All orders retrieved',
            'data' => $orders
        ]);
    }

    // 2. UPDATE STATUS & RESI (Kirim Paket)
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,paid,processing,shipped,completed,cancelled',
            'shipping_resi' => 'nullable|string'
        ]);

        // Logic Khusus: Kalau status diubah jadi 'shipped', WAJIB ada resi
        if ($request->status === 'shipped') {
            if (!$request->shipping_resi) {
                return response()->json(['message' => 'Nomor Resi wajib diisi kalau barang dikirim!'], 422);
            }
            $order->update([
                'status' => 'shipped',
                'shipping_resi' => $request->shipping_resi
            ]);
        } else {
            // Update status biasa (misal: pending -> processing)
            $order->update(['status' => $request->status]);
        }

        return response()->json(['message' => 'Order status updated successfully.', 'data' => $order]);
    }
}
