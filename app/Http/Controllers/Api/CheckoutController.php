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
        $request->validate([
            'items' => 'required|array',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_cost' => 'required|integer',
            'shipping_service' => 'required|string',
            'shipping_courier' => 'required|string',
            'shipping_address' => 'required|string',
            'voucher_code' => 'nullable|string|exists:vouchers,code',
            'note' => 'nullable|string|max:500',
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
            $midtransItemDetails = [];

            // 1. Loop Barang & Hitung Subtotal Normal
            foreach ($request->items as $item) {
                $variant = ProductVariant::with('product')->lockForUpdate()->find($item['variant_id']);

                if (!$variant || $variant->stock < $item['quantity']) {
                    throw new \Exception("Stock for {$variant->product->name} is unavailable!");
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

            // 2. Logic Voucher Multi-Target (ONGKIR vs PRODUCT)
            $discountAmount = 0;
            if ($request->voucher_code) {
                // Tarik data voucher beserta produk yang dapet izin diskon
                $voucher = Voucher::with('products')->where('code', $request->voucher_code)->first();

                if ($voucher && $voucher->stock > 0) {
                    $eligibleAmount = 0;

                    if ($voucher->target === 'shipping') {
                        // 👇 TARGET ONGKIR
                        $eligibleAmount = $request->shipping_cost;
                    } else {
                        // 👇 TARGET PRODUCTS
                        if ($voucher->is_all_products) {
                            $eligibleAmount = $totalPrice;
                        } else {
                            // Cek barang mana aja yang boleh didiskon
                            $allowedIds = $voucher->products->pluck('id')->toArray();
                            foreach ($orderItems as $oi) {
                                if (in_array($oi['product_id'], $allowedIds)) {
                                    $eligibleAmount += ($oi['price'] * $oi['quantity']);
                                }
                            }
                        }
                    }

                    // Hitung Potongan
                    if ($voucher->discount_type == 'percent') {
                        $discountAmount = ($eligibleAmount * $voucher->discount_amount) / 100;
                        // Cek cap maksimal diskon
                        if ($voucher->max_discount_amount && $discountAmount > $voucher->max_discount_amount) {
                            $discountAmount = $voucher->max_discount_amount;
                        }
                    } else {
                        $discountAmount = $voucher->discount_amount;
                    }

                    // Diskon gak boleh lebih gede dari harga aslinya G!
                    $discountAmount = min($discountAmount, $eligibleAmount);
                    $voucher->decrement('stock');
                }
            }

            // 3. Tambahan Item Details buat Midtrans
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

            // 4. Hitung Grand Total & Save Order
            $grandTotal = ($totalPrice + $request->shipping_cost) - $discountAmount;
            if ($grandTotal < 10000) $grandTotal = 10000;

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
                'note' => $request->note,
            ]);

            foreach ($orderItems as $dataItem) {
                $order->items()->create($dataItem);
            }

            // 5. Midtrans Snap Token
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
                'custom_field1' => $request->note,
            ];

            $snapToken = Snap::getSnapToken($midtransParams);
            $order->update(['snap_token' => $snapToken]);

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully!',
                'snap_token' => $snapToken,
                'redirect_url' => "https://app.sandbox.midtrans.com/snap/v2/vtweb/" . $snapToken
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
