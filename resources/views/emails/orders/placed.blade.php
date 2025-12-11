<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body { margin: 0; padding: 0; background-color: #09090b; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #ffffff; }
        .container { width: 100%; max-width: 600px; margin: 0 auto; background-color: #000000; border: 1px solid #27272a; }
        .header { padding: 30px; text-align: center; border-bottom: 1px solid #27272a; }
        .content { padding: 30px; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #52525b; border-top: 1px solid #27272a; }
        .btn { background-color: #ffffff; color: #000000; padding: 12px 24px; text-decoration: none; font-weight: bold; display: inline-block; text-transform: uppercase; letter-spacing: 1px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th { text-align: left; color: #52525b; font-size: 12px; text-transform: uppercase; padding-bottom: 10px; border-bottom: 1px solid #27272a; }
        .table td { padding: 15px 0; border-bottom: 1px solid #27272a; color: #e4e4e7; font-size: 14px; }
        .total-row td { border-bottom: none; font-weight: bold; font-size: 16px; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <h1 style="margin:0; font-size: 24px; letter-spacing: 2px;">HURTSSPACE</h1>
            <p style="margin: 10px 0 0; color: #52525b; font-size: 12px;">ORDER CONFIRMATION</p>
        </div>

        <!-- CONTENT -->
        <div class="content">
            <h2 style="margin-top: 0;">Thanks, {{ $order->user->name }}!</h2>
            <p style="color: #a1a1aa; line-height: 1.5;">
                Order lo <strong>#{{ $order->invoice_number }}</strong> udah kami terima. Selesaikan pembayaran lo biar barangnya cepet kami bungkus.
            </p>

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
                            <div style="font-weight: bold;">{{ $item->product_name }}</div>
                            <div style="font-size: 12px; color: #52525b;">Size: {{ $item->variant_name }}</div>
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td style="text-align: right;">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach

                    <tr>
                        <td colspan="2" style="padding-top: 10px; color: #a1a1aa;">Shipping ({{ strtoupper($order->shipping_courier) }})</td>
                        <td style="text-align: right; padding-top: 10px;">Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="2">TOTAL</td>
                        <td style="text-align: right;">Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- ACTION -->
            <div style="text-align: center; margin-top: 40px;">
                <p style="margin-bottom: 20px; font-size: 12px; color: #a1a1aa;">Belum bayar? Klik tombol di bawah ini:</p>
                <a href="{{ $order->redirect_url ?? '#' }}" class="btn">BAYAR SEKARANG</a>
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
