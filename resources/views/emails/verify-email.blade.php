<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email Hurtsspace</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f8f8f8 0%, #e8e8e8 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        }
        table { border-collapse: collapse; width: 100%; }

        @media only screen and (max-width: 600px) {
            .container { width: 100% !important; }
            .padding { padding: 30px 20px !important; }
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #f8f8f8 0%, #e8e8e8 100%); padding: 50px 20px;">

    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center">

                <!-- Main Container -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="480" class="container" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); overflow: hidden;">

                    <!-- Accent Bar -->
                    <tr>
                        <td style="height: 4px; background: linear-gradient(90deg, #000000 0%, #4a4a4a 100%);"></td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td class="padding" style="padding: 45px 40px;">

                            <!-- Logo -->
                            <div style="margin-bottom: 35px;">
                                <h1 style="color: #000000; font-size: 22px; font-weight: 900; letter-spacing: 3px; margin: 0; text-transform: uppercase;">
                                    HURTSSPACE
                                </h1>
                                <div style="width: 40px; height: 2px; background-color: #000000; margin-top: 12px;"></div>
                            </div>

                            <!-- Greeting -->
                            <h2 style="font-size: 17px; font-weight: 600; margin: 0 0 12px 0; color: #1a1a1a; letter-spacing: 0.3px;">
                                Halo, {{ $name }} 👋
                            </h2>

                            <!-- Message -->
                            <p style="font-size: 14px; line-height: 1.7; margin: 0 0 30px 0; color: #525252;">
                                Selamat datang di Hurtsspace Society. Untuk mengaktifkan akun dan mulai menjelajahi koleksi eksklusif kami, silakan verifikasi email Anda.
                            </p>

                            <!-- Button -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="border-radius: 6px; background: linear-gradient(135deg, #000000 0%, #2d2d2d 100%); box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                                        <a href="{{ $url }}" target="_blank" style="font-size: 13px; font-weight: 600; color: #ffffff; text-decoration: none; padding: 14px 36px; border-radius: 6px; display: inline-block; letter-spacing: 1px; text-transform: uppercase;">
                                            Verifikasi Email
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Divider -->
                            <div style="margin: 30px 0; height: 1px; background: linear-gradient(90deg, transparent, #e5e5e5, transparent);"></div>

                            <!-- Note -->
                            <p style="font-size: 12px; color: #9ca3af; margin: 0; line-height: 1.6;">
                                Link verifikasi berlaku selama 24 jam. Jika tombol tidak berfungsi,
                                <a href="{{ $url }}" style="color: #000000; text-decoration: none; border-bottom: 1px solid #000000;">klik di sini</a>.
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 25px 40px; background: linear-gradient(180deg, #fafafa 0%, #f5f5f5 100%); border-top: 1px solid #ebebeb;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="text-align: left;">
                                        <p style="font-size: 10px; color: #a3a3a3; margin: 0; letter-spacing: 0.5px; text-transform: uppercase;">
                                            Hurtsspace
                                        </p>
                                    </td>
                                    <td style="text-align: right;">
                                        <p style="font-size: 10px; color: #d1d1d1; margin: 0;">
                                            © {{ date('Y') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

                <!-- Bottom Note -->
                <p style="font-size: 11px; color: #a3a3a3; margin: 20px 0 0 0; text-align: center;">
                    Email otomatis — mohon tidak membalas pesan ini
                </p>

            </td>
        </tr>
    </table>

</body>
</html>
