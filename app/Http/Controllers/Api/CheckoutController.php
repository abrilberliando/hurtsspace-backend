<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        // 1. Validasi Input (Tambahin note di sini G!)
        $request->validate([
            'items' => 'required|array',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_cost' => 'required|integer',
            'shipping_service' => 'required|string',
            'shipping_courier' => 'required|string',
            'shipping_address' => 'required|string',
            'voucher_code' => 'nullable|string|exists:vouchers,code',
            'note' => 'nullable|string|max:500', // 👈 WAJIB ADA INI
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
            $midtransItemDetails = [];

            // 2. Loop Barang
            foreach ($request->items as $item) {
                $variant = ProductVariant::with('product')->lockForUpdate()->find($item['variant_id']);

                if (!$variant) {
                    throw new \Exception("Varian produk tidak ditemukan.");
                }

                if ($variant->stock < $item['quantity']) {
                    throw new \Exception("Stok {$variant->product->name} habis, G!");
                }

                $price = (int) $variant->product->price;
                $subtotal = $price * $item['quantity'];
                $totalPrice += $subtotal;

                $orderItems[] = [
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                ];

                $midtransItemDetails[] = [
                    'id' => $variant->id,
                    'price' => $price,
                    'quantity' => $item['quantity'],
                    'name' => substr($variant->product->name . ' (' . $variant->size . ')', 0, 50)
                ];

                $variant->decrement('stock', $item['quantity']);
            }

            // 3. Logic Voucher
            $discountAmount = 0;
            if ($request->voucher_code) {
                $voucher = Voucher::where('code', $request->voucher_code)->first();
                if ($voucher && $voucher->stock > 0) {
                    if ($voucher->discount_type == 'percent') {
                        $discountAmount = ($totalPrice * $voucher->discount_amount) / 100;
                    } else {
                        $discountAmount = $voucher->discount_amount;
                    }
                    $voucher->decrement('stock');
                }
            }

            if ($request->shipping_cost > 0) {
                $midtransItemDetails[] = [
                    'id' => 'SHIPPING',
                    'price' => (int) $request->shipping_cost,
                    'quantity' => 1,
                    'name' => 'Ongkos Kirim (' . strtoupper($request->shipping_courier) . ')'
                ];
            }

            if ($discountAmount > 0) {
                $midtransItemDetails[] = [
                    'id' => 'DISCOUNT',
                    'price' => -((int) $discountAmount),
                    'quantity' => 1,
                    'name' => 'Voucher Discount (' . $request->voucher_code . ')'
                ];
            }

            $grandTotal = ($totalPrice + $request->shipping_cost) - $discountAmount;
            if ($grandTotal < 10000) $grandTotal = 10000;

            // 4. Buat Order Utama (MASUKIN NOTE DI SINI!)
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
                'note' => $request->note, // 👈 INI YANG BIKIN KESIMPEN KE DB, G!
            ]);

            // 5. Simpan Detail Barang
            foreach ($orderItems as $dataItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $dataItem['product_id'],
                    'product_variant_id' => $dataItem['product_variant_id'],
                    'quantity' => $dataItem['quantity'],
                    'price' => $dataItem['price'],
                ]);
            }

            // 6. Request Midtrans
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
                'item_details' => $midtransItemDetails,
                'custom_field1' => $request->note, // Optional: Biar muncul juga di dashboard Midtrans
            ];

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
