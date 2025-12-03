<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // 1. REGISTER (Daftar Member Baru)
    // 1. REGISTER (Daftar Member Baru)
    public function register(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users', // Cek unique
            'password' => 'required|string|min:8',
        ], [
            // Custom Messages bahasa Indonesia
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

        // Cek User ada gak & Password bener gak
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah'],
            ]);
        }

        // Hapus token lama biar bersih (opsional, tapi aman)
        $user->tokens()->delete();

        // Bikin Token Baru
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
        // Hapus token yang lagi dipake sekarang
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

    // 5. Update Profile
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string',
            'phone' => 'nullable|string',
            'address_detail' => 'nullable|string', // Alamat Lengkap
            'city_id' => 'nullable|string',        // ID Area Biteship
            'province_id' => 'nullable|string',    // Nama Kota/Kecamatan buat display (Opsional)
        ]);

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'address_detail' => $request->address_detail,
            'city_id' => $request->city_id,
            // 'province_id' bisa kita pake buat simpen Nama Kota biar gak bingung
            'province_id' => $request->province_id,
        ]);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }
}
