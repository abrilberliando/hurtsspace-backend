<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage; // 👈 Jangan lupa import ini
use Illuminate\Support\Str; // 👈 Ini juga buat cek string
use Illuminate\Validation\ValidationException;

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
            // Validasi Avatar: Harus gambar, max 5MB (biar lega dikit sebelum di-resize)
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
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

        // Handle Upload Foto (Kalau ada)
        if ($request->hasFile('avatar')) {
            try {
                // 1. Hapus foto lama kalau bukan avatar default
                if ($user->avatar && !Str::contains($user->avatar, ['ui-avatars.com', 'default'])) {
                    $oldPath = str_replace(url('storage') . '/', '', $user->avatar);
                    Storage::disk('public')->delete($oldPath);
                }

                // 2. Proses Resize & Crop (Native PHP)
                $file = $request->file('avatar');
                $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();

                // Panggil fungsi helper private di bawah 👇
                $resizedImageContent = $this->resizeImage($file, 500); // Resize ke 500x500px

                // 3. Simpan ke Storage
                Storage::disk('public')->put('avatars/' . $filename, $resizedImageContent);

                // 4. Update URL di object user
                $user->avatar = url('storage/avatars/' . $filename);

            } catch (\Exception $e) {
                return response()->json(['message' => 'Gagal memproses gambar: ' . $e->getMessage()], 500);
            }
        }

        // Update Data Teks
        $user->name = $request->name;

        if ($request->has('phone')) $user->phone = $request->phone;
        if ($request->has('address_detail')) $user->address_detail = $request->address_detail;
        if ($request->has('city_id')) $user->city_id = $request->city_id;
        if ($request->has('province_id')) $user->province_id = $request->province_id;

        // Simpan ke Database
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Helper Private: Resize & Center Crop Image (Square)
     * Menggunakan native PHP GD Library biar gak perlu install package tambahan.
     */
    private function resizeImage($file, $targetSize)
    {
        $info = getimagesize($file);
        $mime = $info['mime'];

        // Load image berdasarkan tipe
        switch ($mime) {
            case 'image/jpeg': $source = imagecreatefromjpeg($file); break;
            case 'image/png': $source = imagecreatefrompng($file); break;
            case 'image/gif': $source = imagecreatefromgif($file); break;
            default: throw new \Exception("Format gambar tidak didukung");
        }

        $width = imagesx($source);
        $height = imagesy($source);

        // Cari sisi terpendek buat patokan crop (biar jadi kotak)
        $min = min($width, $height);
        $offX = ($width - $min) / 2;
        $offY = ($height - $min) / 2;

        // Buat canvas baru kotak kosong
        $newImage = imagecreatetruecolor($targetSize, $targetSize);

        // Handle transparansi buat PNG/GIF
        if ($mime == 'image/png' || $mime == 'image/gif') {
            imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }

        // Copy, Crop (Tengah), dan Resize
        imagecopyresampled($newImage, $source, 0, 0, $offX, $offY, $targetSize, $targetSize, $min, $min);

        // Output ke buffer
        ob_start();
        if ($mime == 'image/jpeg') imagejpeg($newImage, null, 90); // Kualitas JPG 90
        elseif ($mime == 'image/png') imagepng($newImage, null, 9);
        elseif ($mime == 'image/gif') imagegif($newImage);
        $content = ob_get_clean();

        // Bersihin memori
        imagedestroy($source);
        imagedestroy($newImage);

        return $content;
    }
}
