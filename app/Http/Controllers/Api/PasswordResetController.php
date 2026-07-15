<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use App\Mail\ResetPasswordMail;
use Illuminate\Validation\Rules\Password; // 👈 WAJIB IMPORT INI

class PasswordResetController extends Controller
{
    // 1. KIRIM LINK RESET (Forgot Password)
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        // Return sukses palsu biar email gak bisa di-scraping (Security Best Practice)
        if (!$user) {
            return response()->json(['message' => 'Jika email terdaftar, link reset akan dikirim.']);
        }

        // Generate Token
        $token = Str::random(60);

        // Delete old token to clean up & Save New Token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token), // Token di-hash biar aman di DB
            'created_at' => Carbon::now()
        ]);

        // Kirim Email (Link mengarah ke Frontend Next.js)
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000')
            . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

        try {
            Mail::to($user->email)->send(new ResetPasswordMail($user, $frontendUrl));
        } catch (\Exception $e) {
            // Log error asli di server, tapi jangan kasih tau user detailnya
            return response()->json(['message' => 'Failed to send email, please try again later.'], 500);
        }

        return response()->json(['message' => 'Jika email terdaftar, link reset akan dikirim.']);
    }

    // 2. PROSES RESET PASSWORD (Reset Password)
    public function resetPassword(Request $request)
    {
        // 👇 VALIDASI STRICT DI SINI
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => [
                'required',
                'confirmed',
                Password::min(12) // Minimal 12
                    ->letters()   // Wajib ada huruf
                    ->mixedCase() // Wajib Besar & Kecil
                    ->numbers()   // Wajib Angka
                    ->symbols()   // Wajib Simbol
            ],
        ], [
            'password.min' => 'Password must be at least 12 characters!',
            'password.mixed' => 'Password must contain uppercase and lowercase letters.',
            'password.numbers' => 'Password must contain numbers.',
            'password.symbols' => 'Password must contain symbols (!@#$).',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        // Cek Token di DB
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        // Validasi Token: Ada gak? Match gak?
        if (!$record || !Hash::check($request->token, $record->token)) {
            return response()->json(['message' => 'Invalid reset link.'], 400);
        }

        // Cek Expired (Laravel default biasanya 60 menit)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Link expired, please request a new one.'], 400);
        }

        // Update User
        $user = User::where('email', $request->email)->first();
        if (!$user) return response()->json(['message' => 'User not found.'], 404);

        $user->forceFill([
            'password' => Hash::make($request->password)
        ])->setRememberToken(Str::random(60));

        $user->save();

        // Delete token after success so it cannot be used again
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password reset successfully! Please login with your new password.']);
    }
}
