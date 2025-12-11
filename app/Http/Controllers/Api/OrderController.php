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
use Illuminate\Support\Facades\Mail; // 👈 Wajib import Mail
use App\Mail\OrderPlaced; // 👈 Wajib import Mailable Class
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    // 👇 0. CHECKOUT & PAYMENT (CORE LOGIC)
    public function store(Request $request)
    {
        // 1. Validasi Input Dasar
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_cost' => 'required|integer',
            'shipping_courier' => 'nullable|string',
            'shipping_service' => 'nullable|string',
            'shipping_address' => 'required|string', // Pastikan key ini match sama FE
            'voucher_code' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $user = $request->user();
            $invoice = 'INV-' . time() . '-' . Str::random(5);

            // Variabel penampung total duit
            $realTotalAmount = 0;
            $orderItems = [];
            $midtransItems = [];

            // 2. LOOPING ITEM BUAT HITUNG HARGA ASLI (ANTI-CHEAT)
            foreach ($request->items as $item) {
                // Ambil data asli dari Database
                $product = Product::findOrFail($item['product_id']);
                $variant = ProductVariant::findOrFail($item['variant_id']);

                // Cek Stok
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
                    'product_name' => $product->name, // Snapshot nama
                    'variant_name' => $variant->size, // Snapshot size
                    'price' => $price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                ];

                // Siapin data buat Midtrans Item Details (Wajib sama persis)
                $midtransItems[] = [
                    'id' => $product->id . '-' . $variant->id,
                    'price' => $price,
                    'quantity' => $item['quantity'],
                    'name' => substr($product->name, 0, 50), // Limit nama 50 char
                ];

                // Kurangi Stok
                $variant->decrement('stock', $item['quantity']);
            }

            // 3. HITUNG GROSS AMOUNT (Subtotal + Ongkir - Diskon)
            $shippingCost = (int) $request->shipping_cost;
            $grossAmount = $realTotalAmount + $shippingCost;

            // Masukin Ongkir ke list item Midtrans
            if ($shippingCost > 0) {
                $midtransItems[] = [
                    'id' => 'SHIP',
                    'price' => $shippingCost,
                    'quantity' => 1,
                    'name' => 'Shipping Cost',
                ];
            }

            // Handle Voucher (Logic Diskon bisa ditambahkan di sini jika ada)
            // if ($request->voucher_code) { ... }

            // 4. SIMPAN ORDER UTAMA
            $order = Order::create([
                'user_id' => $user->id,
                'invoice_number' => $invoice,
                'total_price' => $grossAmount,
                'status' => 'pending',
                'shipping_address' => $request->shipping_address,
                'shipping_cost' => $shippingCost,
                'shipping_courier' => $request->shipping_courier,
                'shipping_service' => $request->shipping_service,
                // 'voucher_code' => $request->voucher_code,
            ]);

            // Simpan Detail Item
            foreach ($orderItems as $itemData) {
                $order->items()->create($itemData);
            }

            // 5. KONFIGURASI MIDTRANS
            Config::$serverKey = config('midtrans.server_key') ?? env('MIDTRANS_SERVER_KEY');
            Config::$isProduction = config('midtrans.is_production') ?? filter_var(env('MIDTRANS_IS_PRODUCTION'), FILTER_VALIDATE_BOOLEAN);
            Config::$isSanitized = true;
            Config::$is3ds = true;

            $params = [
                'transaction_details' => [
                    'order_id' => $invoice,
                    'gross_amount' => $grossAmount,
                ],
                'item_details' => $midtransItems,
                'customer_details' => [
                    'first_name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
            ];

            // Dapet Snap Token
            $snapToken = Snap::getSnapToken($params);

            // Update Snap Token ke Database
            $order->update(['snap_token' => $snapToken]);

            DB::commit();

            // 👇👇 TRIGGER EMAIL SETELAH COMMIT DB SUKSES 👇👇
            try {
                // Attach redirect URL ke object order secara dinamis untuk email view
                $order->redirect_url = env('FRONTEND_URL', 'http://localhost:3000') . '/dashboard/orders';

                // Kirim email
                Mail::to($user->email)->send(new OrderPlaced($order));

            } catch (\Exception $e) {
                // Jangan sampe error email ngebatalin order, cukup log aja
                Log::error('Gagal kirim email order: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Order created successfully',
                'snap_token' => $snapToken,
                // Redirect URL buat fallback kalau popup gagal
                'redirect_url' => "https://app.sandbox.midtrans.com/snap/v2/vtweb/" . $snapToken
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
            ->with(['items.product.images', 'items.variant'])
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

    // 4. SELESAIKAN PESANAN (Terima Barang)
    public function complete($id) {
        $order = Order::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('status', 'shipped') // Cuma bisa complete kalau status shipped
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order tidak valid untuk diselesaikan.'], 400);
        }

        $order->update(['status' => 'completed']);
        return response()->json(['message' => 'Order selesai! Terima kasih.']);
    }
}
