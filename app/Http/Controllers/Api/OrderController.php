<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;

class OrderController extends Controller
{
    // 👇 0. CHECKOUT & PAYMENT (INI YANG LO CARI BUAT FIX HARGA)
    public function store(Request $request)
    {
        // 1. Validasi Input Dasar
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_cost' => 'required|integer', // Ongkir dari RajaOngkir
            'address' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            $user = $request->user();
            $invoice = 'INV-' . time() . '-' . Str::random(5);

            // Variabel penampung total duit
            $realTotalAmount = 0;
            $orderItems = [];
            $midtransItems = [];

            // 2. LOOPING ITEM BUAT HITUNG HARGA ASLI (JANGAN PERCAYA FRONTEND)
            foreach ($request->items as $item) {
                // Ambil data asli dari Database
                $product = Product::findOrFail($item['product_id']);
                $variant = ProductVariant::findOrFail($item['variant_id']);

                // Cek Stok (Penting!)
                if ($variant->stock < $item['quantity']) {
                    return response()->json(['message' => "Stok {$product->name} ukuran {$variant->size} abis, G!"], 400);
                }

                // Ambil harga dari DB, cast ke Integer buat Midtrans
                $price = (int) $product->price;
                $subtotal = $price * $item['quantity'];

                $realTotalAmount += $subtotal;

                // Siapin data buat disimpen ke tabel order_items
                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $product->name, // Simpan nama saat beli (snapshot)
                    'variant_name' => $variant->size,
                    'price' => $price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                ];

                // Siapin data buat Midtrans Item Details (Wajib sama persis)
                $midtransItems[] = [
                    'id' => $product->id . '-' . $variant->id,
                    'price' => $price,
                    'quantity' => $item['quantity'],
                    'name' => substr($product->name, 0, 50), // Midtrans limit nama 50 char
                ];

                // Kurangi Stok
                $variant->decrement('stock', $item['quantity']);
            }

            // 3. HITUNG GROSS AMOUNT (Subtotal + Ongkir - Diskon dll)
            $shippingCost = (int) $request->shipping_cost;
            $grossAmount = $realTotalAmount + $shippingCost;

            // Masukin Ongkir ke list item Midtrans biar totalnya match
            if ($shippingCost > 0) {
                $midtransItems[] = [
                    'id' => 'SHIP',
                    'price' => $shippingCost,
                    'quantity' => 1,
                    'name' => 'Shipping Cost',
                ];
            }

            // 4. SIMPAN ORDER UTAMA
            $order = Order::create([
                'user_id' => $user->id,
                'invoice_number' => $invoice,
                'total_price' => $grossAmount, // Total yang harus dibayar
                'status' => 'pending',
                'shipping_address' => $request->address,
                'shipping_cost' => $shippingCost,
            ]);

            // Simpan Detail Item
            foreach ($orderItems as $itemData) {
                $order->items()->create($itemData);
            }

            // 5. KONFIGURASI MIDTRANS (Wajib Pake Env)
            Config::$serverKey = config('midtrans.server_key');
            Config::$isProduction = config('midtrans.is_production');
            Config::$isSanitized = true;
            Config::$is3ds = true;

            $params = [
                'transaction_details' => [
                    'order_id' => $invoice,
                    'gross_amount' => $grossAmount, // Total ini WAJIB SAMA dengan sum(midtransItems)
                ],
                'item_details' => $midtransItems, // List item + ongkir
                'customer_details' => [
                    'first_name' => $user->name,
                    'email' => $user->email,
                ],
            ];

            // Dapet Snap Token
            $snapToken = Snap::getSnapToken($params);

            // Update Snap Token ke Database
            $order->update(['snap_token' => $snapToken]);

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully',
                'snap_token' => $snapToken,
                'redirect_url' => "https://app.sandbox.midtrans.com/snap/v2/vtweb/" . $snapToken // Opsional
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Transaction Failed: ' . $e->getMessage()], 500);
        }
    }

    // 1. LIHAT RIWAYAT BELANJA (My Orders)
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items.product.images', 'items.variant']) // Load images biar frontend ganteng
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Order history retrieved',
            'data' => $orders
        ]);
    }

    // 2. LIHAT DETAIL SATU ORDER
    public function show($invoice)
    {
        $order = Order::where('invoice_number', $invoice)
            ->where('user_id', auth()->id())
            ->with(['items.product.images', 'items.variant'])
            ->firstOrFail();

        return response()->json([
            'message' => 'Order detail retrieved',
            'data' => $order
        ]);
    }

    // 3. BATALKAN PESANAN (Cancel Order)
    public function cancel($id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->with('items')
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan atau sudah diproses.'], 404);
        }

        DB::beginTransaction();
        try {
            // Balikin Stok
            foreach ($order->items as $item) {
                $variant = ProductVariant::find($item->product_variant_id);
                if ($variant) {
                    $variant->increment('stock', $item->quantity);
                }
            }

            $order->update(['status' => 'cancelled']);

            DB::commit();
            return response()->json(['message' => 'Order berhasil dibatalkan. Stok aman.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal cancel'], 500);
        }
    }
}
