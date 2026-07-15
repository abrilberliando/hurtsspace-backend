<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    // ========================================================================
    // 1. REGISTER MANUAL (Murni Database)
    // ========================================================================
    public function register(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()
            ],
        ], [
            'email.unique' => 'Email is already registered! Please login.',
            'password.min' => 'Password must be at least 12 characters!',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation Failed', 'errors' => $validator->errors()], 422);
        }

        // 👇 Fix Linter: Kasih tau ini pasti User model
        /** @var \App\Models\User $user */
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'member',
            'points' => 0,
            // 'uid' => (string) Str::uuid(), // Opsional kalau mau generate UID manual
        ]);

        try {
            $user->sendEmailVerificationNotification();

        } catch (\Throwable $e) { // 👈 TANGKAP SEMUA JENIS CRASH

            // Catat errornya di log server (storage/logs/laravel.log)
            Log::error('FAILED TO SEND REGISTER EMAIL (BREVO CRASH?): ' . $e->getMessage());

            // Kita kasih debug error spesifik di response kalau masih 500, biar lo tau penyakitnya.
            // Tapi ini hanya kalau errornya beneran fatal dan nembus ke response.
        }

        // Still return 201 success because account is CREATED
        return response()->json([
            'message' => 'Registration successful! Check your email to verify your account before logging in.',
            'user' => $user,
        ], 201);
    }

    // ========================================================================
    // 2. LOGIN MANUAL
    // ========================================================================
    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => ['Email atau password salah.']]);
        }

        // Cek Verifikasi
        if (!$user->hasVerifiedEmail()) {
             return response()->json([
                 'message' => 'Your email is not verified yet. Please check your inbox/spam!',
                 'not_verified' => true
             ], 403);
        }

        // Fix UID buat user lama (Self-Healing)
        if (!$user->uid) {
            $user->update(['uid' => (string) Str::uuid()]);
        }

        // $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    // ========================================================================
    // 3. LOGIN VIA GOOGLE (Firebase Sync)
    // ========================================================================
    public function loginWithFirebase(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string',
            'uid' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // USER BARU
            $user = User::create([
                'name' => $request->name ?? 'Google User',
                'email' => $request->email,
                'password' => Hash::make(Str::random(32)),
                'role' => 'member',
                'points' => 0,
                'email_verified_at' => now(),
                'uid' => $request->uid,
            ]);
        } else {
            // USER LAMA
            if (!$user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }
            if ($user->uid !== $request->uid) {
                $user->update(['uid' => $request->uid]);
            }
        }

        // $user->tokens()->delete();
        $token = $user->createToken('google-auth')->plainTextToken;

        return response()->json([
            'message' => 'Login with Google successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    // ========================================================================
    // 4. VERIFIKASI EMAIL HANDLER
    // ========================================================================
    public function verifyEmail(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // 1. Cek validitas link (Signature & Expiry)
        if (!$request->hasValidSignature()) {
            // Kalau expired, redirect ke FE dengan error param
            $errorUrl = env('FRONTEND_URL', 'http://localhost:3000') . '/verify-email?error=' . urlencode('Link expired or invalid.');
            return redirect($errorUrl);
        }

        // 2. Mark verified if not yet
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        // 3. 👇 REDIRECT KE HALAMAN KHUSUS 'VERIFY-EMAIL' (BUKAN LOGIN)
        // Make sure your .env FRONTEND_URL is correct (e.g. https://hurtsspace.com)
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000') . '/verify-email?verified=1';

        return redirect($frontendUrl);
    }

    public function resendVerification(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
             return response()->json(['message' => 'Verification link has been resent! Check your email.']);
        }

        if ($request->user() && $request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Akun lo udah aktif kok!']);
        }

        // Panggil langsung method sendEmailVerificationNotification
        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link has been resent! Check your email.']);
    }

    // ========================================================================
    // 5. UPDATE PROFILE
    // ========================================================================
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // Validasi
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address_detail' => 'nullable|string',
            'city_id' => 'nullable|string',
            'province_id' => 'nullable|string',
        ]);

        if ($validator->fails()) return response()->json(['message' => 'Invalid profile data.', 'errors' => $validator->errors()], 422);

        DB::beginTransaction();

        try {
            $user->name = $request->name;
            if ($request->has('phone')) $user->phone = $request->phone;
            if ($request->has('address_detail')) $user->address_detail = $request->address_detail;
            if ($request->has('city_id')) $user->city_id = $request->city_id;
            if ($request->has('province_id')) $user->province_id = $request->province_id;

            $user->save();
            $user->refresh();

            DB::commit();
            return response()->json(['message' => 'Profile updated successfully', 'user' => $user]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auth Update Profile Failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update profile: ' . $e->getMessage()], 500);
        }
    }

    public function me(Request $request) { return response()->json($request->user()); }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
