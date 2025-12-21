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
            'email.unique' => 'Waduh, Email ini sudah terdaftar G! Coba Login aja.',
            'password.min' => 'Password minimal 12 karakter ya, biar aman!',
            'password.confirmed' => 'Password konfirmasi gak cocok nih.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi Gagal', 'errors' => $validator->errors()], 422);
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
            Log::error('GAGAL KIRIM EMAIL REGISTER (BREVO CRASH?): ' . $e->getMessage());

            // Kita kasih debug error spesifik di response kalau masih 500, biar lo tau penyakitnya.
            // Tapi ini hanya kalau errornya beneran fatal dan nembus ke response.
        }

        // Tetap return sukses 201 karena akun SUDAH JADI
        return response()->json([
            'message' => 'Registrasi berhasil! Cek email lo buat verifikasi akun sebelum login (atau minta kirim ulang di halaman login).',
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
                 'message' => 'Email lo belum diverifikasi. Cek inbox/spam email lo ya G!',
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
            'avatar' => 'nullable|string',
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
                'avatar' => $request->avatar,
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
            if (!$user->avatar && $request->avatar) {
                $user->update(['avatar' => $request->avatar]);
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
            $errorUrl = env('FRONTEND_URL', 'http://localhost:3000') . '/verify-email?error=' . urlencode('Link kadaluwarsa atau tidak valid.');
            return redirect($errorUrl);
        }

        // 2. Mark verified kalau belum
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        // 3. 👇 REDIRECT KE HALAMAN KHUSUS 'VERIFY-EMAIL' (BUKAN LOGIN)
        // Pastikan .env FRONTEND_URL lo sudah benar (misal: https://hurtsspace.com)
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000') . '/verify-email?verified=1';

        return redirect($frontendUrl);
    }

    public function resendVerification(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
             return response()->json(['message' => 'Link verifikasi udah dikirim ulang! Cek email.']);
        }

        if ($request->user() && $request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Akun lo udah aktif kok!']);
        }

        // Panggil langsung method sendEmailVerificationNotification
        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Link verifikasi udah dikirim ulang! Cek email.']);
    }

    // ========================================================================
    // 5. UPDATE PROFILE
    // ========================================================================
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        // ... (Logic update profile sama persis, gak ada perubahan di sini)
        // Code disingkat biar gak kepanjangan, copy dari versi sebelumnya kalau perlu full logicnya

        // Validasi
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address_detail' => 'nullable|string',
            'city_id' => 'nullable|string',
            'province_id' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) return response()->json(['message' => 'Data profil gak valid nih.', 'errors' => $validator->errors()], 422);

        $oldAvatarUrl = $user->avatar;
        $newFilename = null;
        $shouldDeleteOldFile = false;

        DB::beginTransaction();

        try {
            if ($request->hasFile('avatar')) {
                $shouldDeleteOldFile = $user->avatar && !Str::contains($user->avatar, ['ui-avatars.com', 'default', 'googleusercontent.com']);
                $manager = new ImageManager(new Driver());
                $image = $manager->read($request->file('avatar')->getRealPath());
                $image->cover(500, 500);
                $newFilename = 'avatar_' . $user->id . '_' . time() . '.webp';
                Storage::disk('public')->put('avatars/' . $newFilename, $image->encode());
                $user->avatar = url('storage/avatars/' . $newFilename);
            }

            $user->name = $request->name;
            if ($request->has('phone')) $user->phone = $request->phone;
            if ($request->has('address_detail')) $user->address_detail = $request->address_detail;
            if ($request->has('city_id')) $user->city_id = $request->city_id;
            if ($request->has('province_id')) $user->province_id = $request->province_id;

            $user->save();
            $user->refresh();

            if ($shouldDeleteOldFile) {
                $oldPath = str_replace(url('storage') . '/', '', $oldAvatarUrl);
                if (Storage::disk('public')->exists($oldPath)) Storage::disk('public')->delete($oldPath);
            }

            DB::commit();
            return response()->json(['message' => 'Profile updated successfully', 'user' => $user]);

        } catch (\Exception $e) {
            DB::rollBack();
            if ($newFilename) Storage::disk('public')->delete('avatars/' . $newFilename);
            Log::error('Auth Update Profile Failed: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal update profile: ' . $e->getMessage()], 500);
        }
    }

    public function me(Request $request) { return response()->json($request->user()); }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
