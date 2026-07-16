<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification; // 👈 Wajib Import
use App\Mail\OrderPaid;
use App\Notifications\OrderPaidToAdmin; // 👈 Wajib Import Notifikasi Admin

class WebhookController extends Controller
{
    public function handler(Request $request)
    {
        // 1. Log Request (Penting buat debugging di production)
        Log::info('Midtrans Webhook Received: ' . json_encode($request->all()));

        $serverKey = config('midtrans.server_key') ?? env('MIDTRANS_SERVER_KEY');

        // 2. Validasi Signature Key (SECURITY LAYER UTAMA)
        // Pastikan urutan string: order_id + status_code + gross_amount + ServerKey
        $signatureString = $request->order_id . $request->status_code . $request->gross_amount . $serverKey;
        $hashed = hash("sha512", $signatureString);

        if ($hashed !== $request->signature_key) {
            Log::error("Invalid Signature Key! Expect: $hashed vs Got: {$request->signature_key}");
            return response()->json(['message' => 'Invalid Signature'], 403);
        }

        $transactionStatus = $request->transaction_status;
        $type = $request->payment_type;
        $orderId = $request->order_id;
        $fraudStatus = $request->fraud_status;

        // Cari Order
        $order = Order::where('invoice_number', $orderId)->first();

        if (!$order) {
            Log::error('Order not found: ' . $orderId);
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Kalau order udah paid/shipped/cancelled, stop proses (Idempotency)
        if ($order->status !== 'pending') {
             return response()->json(['message' => 'Order status already updated']);
        }

        DB::beginTransaction();
        try {
            // 3. Logic Status Midtrans
            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'challenge') {
                    $order->update(['status' => 'pending']);
                } else if ($fraudStatus == 'accept') {
                    $this->markAsPaid($order);
                }
            } else if ($transactionStatus == 'settlement') {
                $this->markAsPaid($order); // Payment received = Paid
            } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
                $this->markAsCancelled($order); // Logic Cancel & Restock
            } else if ($transactionStatus == 'pending') {
                $order->update(['status' => 'pending']);
            }

            DB::commit();
            return response()->json(['message' => 'Webhook processed']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Webhook Failed: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing webhook'], 500);
        }
    }

    // Helper: Mark Paid, Send Email to User & Admin, Add Points
    private function markAsPaid($order)
    {
        $order->update(['status' => 'paid']);

        // A. LOGIC POIN MEMBER 💎
        $user = $order->user;
        if ($user && $user->role === 'member') {
            // Rumus: Total Belanja dibagi 10.000
            $pointsEarned = floor($order->total_price / 10000);

            if ($pointsEarned > 0) {
                $user->increment('points', $pointsEarned);
                Log::info("User {$user->id} dapet {$pointsEarned} poin dari order {$order->invoice_number}");
            }
        }

        // B. KIRIM EMAIL KONFIRMASI KE USER
        try {
            Mail::to($order->user->email)->send(new OrderPaid($order));
        } catch (\Exception $e) {
            Log::error('Failed to send user paid email: ' . $e->getMessage());
        }

        // C. KIRIM NOTIFIKASI KE ADMIN (Email + Database)
        try {
            $adminEmail = env('ADMIN_NOTIFY_EMAIL'); // Pastikan ini ada di .env
            if ($adminEmail) {
                Notification::route('mail', $adminEmail)
                    ->notify(new OrderPaidToAdmin($order));
                Log::info("Notif admin dikirim ke: $adminEmail");
            }
        } catch (\Exception $e) {
            Log::error('Failed to send admin notification: ' . $e->getMessage());
        }
    }

    // Helper: Tandai Cancel & Restock Barang
    private function markAsCancelled($order)
    {
        // 1. Balikin Stok ke Gudang (Restock)
        foreach ($order->items as $item) {
            $variant = ProductVariant::find($item->product_variant_id);
            if ($variant) {
                $variant->increment('stock', $item->quantity);
            }
        }

        // 2. Update Status
        $order->update(['status' => 'cancelled']);
        Log::info("Order {$order->invoice_number} dicancel otomatis oleh sistem/midtrans. Stok dikembalikan.");
    }
}
