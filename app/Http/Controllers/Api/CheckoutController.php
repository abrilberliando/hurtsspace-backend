<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Voucher; // 👈 Jangan lupa import ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'items' => 'required|array', // [{variant_id: 1, quantity: 2}]
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_cost' => 'required|integer',
            'shipping_service' => 'required|string',
            'shipping_courier' => 'required|string',
            'shipping_address' => 'required|string',
            'voucher_code' => 'nullable|string|exists:vouchers,code', // 👈 Validasi Voucher
        ]);

        // Setup Midtrans
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;

        DB::beginTransaction();

        try {
            $user = $request->user();
            $totalPrice = 0;
            $orderItems = [];
            $midtransItemDetails = []; // Array khusus buat Midtrans

            // 2. Loop Barang: Hitung Harga, Cek Stok, Siapkan Data
            foreach ($request->items as $item) {
                $variant = ProductVariant::with('product')->lockForUpdate()->find($item['variant_id']);

                if (!$variant) {
                    throw new \Exception("Varian produk ID " . $item['variant_id'] . " tidak ditemukan.");
                }

                if ($variant->stock < $item['quantity']) {
                    throw new \Exception("Stok {$variant->product->name} ({$variant->size}) habis atau kurang, G!");
                }

                $price = (int) $variant->product->price;
                $subtotal = $price * $item['quantity'];
                $totalPrice += $subtotal;

                // Data buat disimpen ke database order_items
                $orderItems[] = [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                ];

                // Data buat dikirim ke Midtrans (Item Details)
                $midtransItemDetails[] = [
                    'id' => $variant->id,
                    'price' => $price,
                    'quantity' => $item['quantity'],
                    'name' => substr($variant->product->name . ' (' . $variant->size . ')', 0, 50)
                ];

                // Kurangi Stok Barang
                $variant->decrement('stock', $item['quantity']);
            }

            // 3. Logic Diskon Voucher
            $discountAmount = 0;
            if ($request->voucher_code) {
                $voucher = Voucher::where('code', $request->voucher_code)->first();

                // Cek validitas & stok voucher lagi biar aman
                if ($voucher && $voucher->stock > 0) {
                    if ($voucher->discount_type == 'percent') {
                        $discountAmount = ($totalPrice * $voucher->discount_amount) / 100;
                    } else {
                        $discountAmount = $voucher->discount_amount;
                    }

                    // Kurangi stok voucher
                    $voucher->decrement('stock');
                }
            }

            // Tambahkan Ongkir ke rincian Midtrans
            if ($request->shipping_cost > 0) {
                $midtransItemDetails[] = [
                    'id' => 'SHIPPING',
                    'price' => (int) $request->shipping_cost,
                    'quantity' => 1,
                    'name' => 'Ongkos Kirim (' . strtoupper($request->shipping_courier) . ')'
                ];
            }

            // Tambahkan Diskon ke rincian Midtrans (Sebagai item negatif)
            if ($discountAmount > 0) {
                $midtransItemDetails[] = [
                    'id' => 'DISCOUNT',
                    'price' => -((int) $discountAmount), // Harga minus
                    'quantity' => 1,
                    'name' => 'Voucher Discount (' . $request->voucher_code . ')'
                ];
            }

            // Hitung Grand Total (Total Barang + Ongkir - Diskon)
            // Min 10000 biar Midtrans gak error kalo gratisan
            $grandTotal = ($totalPrice + $request->shipping_cost) - $discountAmount;
            if ($grandTotal < 10000) $grandTotal = 10000;

            // 4. Buat Order Utama di Database
            $invoice = 'INV-' . time() . '-' . $user->id;

            $order = Order::create([
                'user_id' => $user->id,
                'invoice_number' => $invoice,
                'total_price' => $grandTotal,
                'status' => 'pending',
                'shipping_cost' => $request->shipping_cost,
                'shipping_courier' => $request->shipping_courier,
                'shipping_service' => $request->shipping_service,
                'shipping_address' => $request->shipping_address,
            ]);

            // 5. Simpan Detail Barang ke Database
            foreach ($orderItems as $dataItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $dataItem['product_id'],
                    'product_variant_id' => $dataItem['product_variant_id'],
                    'quantity' => $dataItem['quantity'],
                    'price' => $dataItem['price'],
                ]);
            }

            // 6. Request Snap Token ke Midtrans
            $midtransParams = [
                'transaction_details' => [
                    'order_id' => $invoice,
                    'gross_amount' => (int) $grandTotal,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? '08123456789',
                ],
                'item_details' => $midtransItemDetails, // Pake array yang udah lengkap tadi
            ];

            $snapToken = Snap::getSnapToken($midtransParams);

            // Update token ke order
            $order->update(['snap_token' => $snapToken]);

            DB::commit();

            return response()->json([
                'message' => 'Order berhasil dibuat!',
                'snap_token' => $snapToken,
                'redirect_url' => "https://app.sandbox.midtrans.com/snap/v2/vtweb/" . $snapToken
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
