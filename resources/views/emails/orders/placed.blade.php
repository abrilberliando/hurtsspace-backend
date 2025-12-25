<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status - Hurtsspace</title>
    <style>
        body { margin: 0; padding: 0; background-color: #09090b; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #ffffff; }
        .container { width: 100%; max-width: 600px; margin: 0 auto; background-color: #000000; border: 1px solid #27272a; }
        .header { padding: 30px; text-align: center; border-bottom: 1px solid #27272a; }
        .content { padding: 30px; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #52525b; border-top: 1px solid #27272a; }

        /* Tombol Dinamis */
        .btn { padding: 12px 24px; text-decoration: none; font-weight: bold; display: inline-block; text-transform: uppercase; letter-spacing: 1px; border-radius: 4px; }
        .btn-pay { background-color: #facc15; color: #000000; } /* Kuning buat Bayar */
        .btn-check { background-color: #ffffff; color: #000000; } /* Putih buat Cek Order */

        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th { text-align: left; color: #52525b; font-size: 12px; text-transform: uppercase; padding-bottom: 10px; border-bottom: 1px solid #27272a; }
        .table td { padding: 15px 0; border-bottom: 1px solid #27272a; color: #e4e4e7; font-size: 14px; }
        .total-row td { border-bottom: none; font-weight: bold; font-size: 16px; padding-top: 20px; }

        /* Badge Status */
        .status-badge { display: inline-block; padding: 4px 12px; font-size: 12px; font-weight: bold; border-radius: 4px; text-transform: uppercase; letter-spacing: 1px; }
        .status-pending { background-color: #facc15; color: #000; }
        .status-paid { background-color: #22c55e; color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1 style="margin:0; font-size: 24px; letter-spacing: 2px;">HURTSSPACE</h1>
            <p style="margin: 10px 0 0; color: #52525b; font-size: 12px;">
                @if(isset($is_paid) && $is_paid)
                    PAYMENT SUCCESS
                @else
                    ORDER CONFIRMATION
                @endif
            </p>
        </div>

        <!-- CONTENT -->
        <div class="content">

            {{-- Bagian Greeting & Status --}}
            @if(isset($is_paid) && $is_paid)
                <div style="text-align: center; margin-bottom: 30px;">
                    <span class="status-badge status-paid">PAID</span>
                </div>
                <h2 style="margin-top: 0;">Thanks, {{ $order->user->name }}! 🔥</h2>
                <p style="color: #a1a1aa; line-height: 1.5;">
                    Pembayaran untuk Order <strong>#{{ $order->invoice_number }}</strong> udah kami terima. Barang lo lagi disiapin dan bakal segera meluncur!
                </p>
            @else
                <div style="text-align: center; margin-bottom: 30px;">
                    <span class="status-badge status-pending">PENDING PAYMENT</span>
                </div>
                <h2 style="margin-top: 0;">Hi, {{ $order->user->name }}!</h2>
                <p style="color: #a1a1aa; line-height: 1.5;">
                    Order lo <strong>#{{ $order->invoice_number }}</strong> udah masuk nih. Tapi belum lunas ya, G. Selesaikan pembayaran biar barangnya gak diambil orang lain.
                </p>
            @endif

            <!-- ORDER DETAILS -->
            <table class="table">
                <thead>
                    <tr>
                        <th width="60%">Item</th>
                        <th width="10%">Qty</th>
                        <th width="30%" style="text-align: right;">Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>
                            <div style="font-weight: bold;">
                                {{-- LOGIC PINTAR: Cek Snapshot dulu, kalau kosong/0 ambil dari Relasi Produk --}}
                                {{ ($item->product_name && $item->product_name !== '0') ? $item->product_name : ($item->product->name ?? 'Produk Tidak Tersedia') }}
                            </div>
                            <div style="font-size: 12px; color: #52525b;">
                                {{-- LOGIC PINTAR: Cek Snapshot Size, kalau kosong/0 ambil dari Relasi Variant --}}
                                Size: {{ ($item->variant_name && $item->variant_name !== '0') ? $item->variant_name : ($item->variant->size ?? '-') }}
                            </div>
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td style="text-align: right;">
                            {{-- LOGIC PINTAR: Kalau subtotal 0, hitung manual Harga x Qty --}}
                            Rp {{ number_format(($item->subtotal > 0 ? $item->subtotal : ($item->price * $item->quantity)), 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach

                    <tr>
                        <td colspan="2" style="padding-top: 10px; color: #a1a1aa;">Shipping ({{ strtoupper($order->shipping_courier ?? 'KURIR') }})</td>
                        <td style="text-align: right; padding-top: 10px;">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="2">TOTAL</td>
                        <td style="text-align: right;">Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- ACTION BUTTON -->
            <div style="text-align: center; margin-top: 40px;">
                @if(isset($is_paid) && $is_paid)
                    <p style="margin-bottom: 20px; font-size: 12px; color: #a1a1aa;">Pantau status pengiriman di sini:</p>
                    <a href="{{ $order->redirect_url ?? 'https://hurtsspace.com/dashboard/orders' }}" class="btn btn-check">CEK STATUS ORDER</a>
                @else
                    <p style="margin-bottom: 20px; font-size: 12px; color: #a1a1aa;">Klik tombol di bawah untuk lanjut bayar:</p>
                    <a href="{{ $order->redirect_url ?? 'https://hurtsspace.com/dashboard/orders' }}" class="btn btn-pay">BAYAR SEKARANG</a>
                @endif
            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} Hurtsspace. All rights reserved.</p>
            <p>Surabaya, Indonesia</p>
        </div>
    </div>
</body>
</html>
