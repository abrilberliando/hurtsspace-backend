<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password Hurtsspace</title>
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
                                Halo, {{ $user->name }} 🔐
                            </h2>

                            <!-- Message -->
                            <p style="font-size: 14px; line-height: 1.7; margin: 0 0 15px 0; color: #525252;">
                                Kami menerima permintaan untuk mereset password akun Hurtsspace Anda. Klik tombol di bawah untuk membuat password baru.
                            </p>

                            <p style="font-size: 14px; line-height: 1.7; margin: 0 0 30px 0; color: #525252;">
                                Jika Anda tidak meminta reset password, abaikan email ini dan akun Anda akan tetap aman.
                            </p>

                            <!-- Button -->
                            <div style="text-align: center; margin: 0 0 20px 0;">
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                    <tr>
                                        <td style="border-radius: 8px; background: linear-gradient(135deg, #1a1a1a 0%, #404040 100%); box-shadow: 0 4px 14px rgba(0,0,0,0.2);">
                                            <a href="{{ $url }}" target="_blank" style="font-size: 12px; font-weight: 600; color: #ffffff; text-decoration: none; padding: 14px 40px; border-radius: 8px; display: inline-block; letter-spacing: 0.8px; text-transform: uppercase;">
                                                🔑 Reset Password
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Alternative minimal link -->
                            <p style="font-size: 12px; color: #71717a; margin: 15px 0 0 0; text-align: center;">
                                atau <a href="{{ $url }}" style="color: #000000; text-decoration: none; border-bottom: 1px solid #000000; font-weight: 500;">reset via link ini</a>
                            </p>

                            <!-- Divider -->
                            <div style="margin: 30px 0; height: 1px; background: linear-gradient(90deg, transparent, #e5e5e5, transparent);"></div>

                            <!-- Security Note -->
                            <div style="background-color: #fafafa; border-left: 3px solid #000000; padding: 15px; border-radius: 4px;">
                                <p style="font-size: 12px; color: #525252; margin: 0 0 8px 0; font-weight: 600;">
                                    ⚠️ Catatan Keamanan
                                </p>
                                <p style="font-size: 12px; color: #71717a; margin: 0; line-height: 1.6;">
                                    Link reset password berlaku selama <strong>1 jam</strong>. Jangan bagikan link ini ke siapapun. Jika Anda merasa ada aktivitas mencurigakan, segera hubungi tim support kami.
                                </p>
                            </div>

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
