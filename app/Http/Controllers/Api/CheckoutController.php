<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
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
            'items' => 'required|array',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_cost' => 'required|integer',
            'shipping_service' => 'required|string',
            'shipping_courier' => 'required|string',
            'shipping_address' => 'required|string',
        ]);

        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;

        DB::beginTransaction();

        try {
            $user = $request->user();
            $totalPrice = 0;
            $orderItems = [];

            // 2. Hitung Ulang & Cek Stok
            foreach ($request->items as $item) {
                $variant = ProductVariant::with('product')->lockForUpdate()->find($item['variant_id']);

                if (!$variant) {
                    throw new \Exception("Varian produk ID " . $item['variant_id'] . " tidak ditemukan.");
                }

                if ($variant->stock < $item['quantity']) {
                    throw new \Exception("Stok {$variant->product->name} ({$variant->size}) habis atau kurang, G!");
                }

                $price = $variant->product->price;
                $subtotal = $price * $item['quantity'];
                $totalPrice += $subtotal;

                // Simpan data lengkap ke array sementara
                $orderItems[] = [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'name' => $variant->product->name, // Tambahin Nama buat Midtrans
                ];

                $variant->decrement('stock', $item['quantity']);
            }

            $grandTotal = $totalPrice + $request->shipping_cost;
            $invoice = 'INV-' . time() . '-' . $user->id;

            // 3. Simpan Order Utama
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

            // Simpan Detail Order Items
            foreach ($orderItems as $dataItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $dataItem['product_id'],
                    'product_variant_id' => $dataItem['product_variant_id'],
                    'quantity' => $dataItem['quantity'],
                    'price' => $dataItem['price'],
                ]);
            }

            // 4. Request Snap Token Midtrans (FIXED LOGIC DI SINI)
            // Kita pake $orderItems yang udah lengkap datanya
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
                'item_details' => array_map(function($item) {
                    return [
                        'id' => $item['product_variant_id'],
                        'price' => (int) $item['price'],
                        'quantity' => $item['quantity'],
                        'name' => substr($item['name'], 0, 50) // Nama produk max 50 char biar aman
                    ];
                }, $orderItems), // <--- Pake $orderItems, bukan $request->items
            ];

            // Masukin Ongkir sebagai "Item" tambahan di Midtrans biar totalnya match
            if ($request->shipping_cost > 0) {
                $midtransParams['item_details'][] = [
                    'id' => 'SHIPPING',
                    'price' => (int) $request->shipping_cost,
                    'quantity' => 1,
                    'name' => 'Ongkos Kirim (' . strtoupper($request->shipping_courier) . ')'
                ];
            }

            $snapToken = Snap::getSnapToken($midtransParams);

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
