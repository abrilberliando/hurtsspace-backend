<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Notification;

class WebhookController extends Controller
{
    public function handler(Request $request)
    {
        // 1. Setup Konfigurasi Midtrans
        Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;

        try {
            // 2. Tangkap Notifikasi dari Midtrans
            // $notif = new Notification();

            $transactionStatus = $request->transaction_status;
            $type = $request->payment_type;
            $orderId = $request->order_id;
            $fraud = $request->fraud_status;

            // Cari Order berdasarkan Invoice Number
            $order = Order::where('invoice_number', $orderId)->first();

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            // 3. Logic Status Otomatis
            if ($transactionStatus == 'capture') {
                if ($type == 'credit_card') {
                    if ($fraud == 'challenge') {
                        $order->update(['status' => 'pending']);
                    } else {
                        $order->update(['status' => 'paid']);
                    }
                }
            } else if ($transactionStatus == 'settlement') {
                // 1. Update Status Order jadi Paid
                $order->update(['status' => 'paid']);

                // 2. LOGIC POIN MEMBER (Hurtsspace Society) 💎
                // Cek apakah user-nya Member (bukan Admin, walau admin jarang belanja sih)
                $user = $order->user;

                if ($user && $user->role === 'member') {
                    // Rumus: Total Belanja dibagi 10.000
                    // Contoh: Belanja 150.000 -> Dapet 15 Poin
                    $pointsEarned = floor($order->total_price / 10000);

                    if ($pointsEarned > 0) {
                        // Tambahin poin ke tabel users
                        $user->increment('points', $pointsEarned);
                    }
                }

            } else if ($transactionStatus == 'pending') {
                $order->update(['status' => 'pending']);
            } else if ($transactionStatus == 'deny') {
                $order->update(['status' => 'cancelled']);
            } else if ($transactionStatus == 'expire') {
                $order->update(['status' => 'cancelled']);
                // Balikin stok kalau expire (Optional, tapi bagus buat inventory)
            } else if ($transactionStatus == 'cancel') {
                $order->update(['status' => 'cancelled']);
            }

            return response()->json(['message' => 'Webhook received']);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error handling webhook: ' . $e->getMessage()], 500);
        }
    }
}
