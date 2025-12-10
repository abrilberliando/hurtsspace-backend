<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AuthController extends Controller
{
    // 1. REGISTER (Daftar Member Baru)
    public function register(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ], [
            'email.unique' => 'Waduh, Email ini sudah terdaftar G! Coba Login aja.',
            'email.email' => 'Format email salah bro.',
            'password.min' => 'Password minimal 8 karakter ya.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi Gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'member',
            'points' => 0,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Welcome to Hurtsspace Society!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }

    // 2. LOGIN (Masuk)
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah'],
            ]);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    // 3. LOGOUT (Keluar & Hapus Token)
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    // 4. ME (Cek Profile Sendiri)
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    // 👇 5. UPDATE PROFILE (Updated with Smart Resize Avatar)
     public function updateProfile(Request $request)
    {
        $user = $request->user();

        // Validasi Manual
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address_detail' => 'nullable|string',
            'city_id' => 'nullable|string',
            'province_id' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Tambah webp
        ], [
            'avatar.max' => 'Waduh, fotonya kegedean G! Maksimal 5MB ya.',
            'avatar.image' => 'File harus berupa gambar.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data profil gak valid nih.',
                'errors' => $validator->errors()
            ], 422);
        }

        // 👇 FIX UTAMA: INI HARUS DI-DECLARE SEBELUM TRY BLOCK!
        $oldAvatarUrl = $user->avatar;
        $newFilename = null;
        $shouldDeleteOldFile = false; // 👈 INI HARUS DI-DECLARE AWAL

        // 👇 START DATABASE TRANSACTION
        DB::beginTransaction();

        try {
            // Handle Upload Foto (Kalau ada)
            if ($request->hasFile('avatar')) {
                // 1. Tentukan apakah file lama perlu dihapus
                $shouldDeleteOldFile = $user->avatar && !Str::contains($user->avatar, ['ui-avatars.com', 'default']);

                // 2. Setup Manager & Proses Resize (Intervention Image)
                $manager = new ImageManager(new Driver());
                $image = $manager->read($request->file('avatar')->getRealPath());
                $image->cover(500, 500);

                // 3. Simpan ke Storage
                $newFilename = 'avatar_' . $user->id . '_' . time() . '.webp';
                Storage::disk('public')->put('avatars/' . $newFilename, $image->encode());

                // 4. Update URL di object user
                $user->avatar = url('storage/avatars/' . $newFilename);
            }

            // Update Data Teks
            $user->name = $request->name;

            if ($request->has('phone')) $user->phone = $request->phone;
            if ($request->has('address_detail')) $user->address_detail = $request->address_detail;
            if ($request->has('city_id')) $user->city_id = $request->city_id;
            if ($request->has('province_id')) $user->province_id = $request->province_id;

            // 5. Simpan ke Database (KALAU INI BERHASIL, BARU COMMIT)
            $user->save();
            $user->refresh(); // Ambil data terbaru (penting!)

            // 6. Hapus File Lama dari Disk (SETELAH DATABASE UPDATE SUKSES)
            if ($shouldDeleteOldFile) {
                $oldPath = str_replace(url('storage') . '/', '', $oldAvatarUrl);
                Storage::disk('public')->delete($oldPath);
            }

            DB::commit(); // Transaksi aman, simpan semua perubahan

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            // 👇 ROLLBACK JIKA ADA ERROR
            DB::rollBack();

            // Kalau ada file baru yang sempat disimpan, kita hapus file itu juga
            if ($newFilename) {
                 Storage::disk('public')->delete('avatars/' . $newFilename);
            }

            // Tambahkan logging error asli
            Log::error('Auth Update Profile Failed: ' . $e->getMessage(), ['exception' => $e]);

            return response()->json(['message' => 'Gagal memproses update profile: ' . $e->getMessage()], 500);
        }
    }
}
