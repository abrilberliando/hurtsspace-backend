<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB; // 👈 Transaction
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class BannerController extends Controller
{
    // 1. PUBLIC: Ambil SEMUA Banner yang Statusnya AKTIF
    public function getActive()
    {
        // Ambil data dari DB
        $banners = Banner::where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        // 👇 FIX: Transformasi Data sebelum dikirim ke Frontend
        $banners->transform(function ($banner) {
            // 1. Pastikan Position adalah Integer (Biar match sama frontend)
            $banner->position = (int) $banner->position;

            // 2. Paksa URL Gambar jadi HTTPS di Production (Anti Mixed Content)
            if (app()->environment('production')) {
                $banner->image_left = str_replace('http://', 'https://', $banner->image_left);
                $banner->image_right = str_replace('http://', 'https://', $banner->image_right);
            }

            return $banner;
        });

        return response()->json(['data' => $banners]);
    }

    // 2. ADMIN: List Semua Banner
    public function index()
    {
        return response()->json(['data' => Banner::latest()->get()]);
    }

    // 3. ADMIN: Upload Banner Baru (Optimized)
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'position' => 'required|in:1,2',
            // Validasi gambar max 5MB sebelum di-resize
            'image_left' => 'required|image|max:5120',
            'image_right' => 'required|image|max:5120',
            'link_url' => 'required|string',
        ]);

        DB::beginTransaction();
        $uploadedCloudinaryIds = [];
        try {
            // Helper function buat process image dengan Cloudinary
            $processImage = function($file) use (&$uploadedCloudinaryIds) {
                $uploadResult = cloudinary()->uploadApi()->upload($file->getRealPath(), [
                    'folder' => 'hspace/banners',
                    'format' => 'webp',
                    'transformation' => [
                        'width' => 1200,
                        'crop' => 'scale'
                    ]
                ]);
                $uploadedCloudinaryIds[] = $uploadResult['public_id'];
                return $uploadResult['secure_url'];
            };

            // Process kedua gambar
            $urlLeft = $processImage($request->file('image_left'));
            $urlRight = $processImage($request->file('image_right'));

            // Save to DB
            $banner = Banner::create([
                'title' => $request->title,
                'position' => $request->position,
                'image_left' => $urlLeft,
                'image_right' => $urlRight,
                'link_url' => $request->link_url,
                'is_active' => true
            ]);

            DB::commit();

            return response()->json(['message' => 'Banner created successfully', 'data' => $banner], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedCloudinaryIds as $publicId) {
                try { cloudinary()->uploadApi()->destroy($publicId); } catch (\Exception $ex) {}
            }
            return response()->json(['message' => 'Failed to upload banner: ' . $e->getMessage()], 500);
        }
    }

    // 4. ADMIN: Update Banner (Ganti Info atau Gambar)
    public function update(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);

        $request->validate([
            'title' => 'required|string',
            'link_url' => 'required|string',
            'image_left' => 'nullable|image|max:5120',
            'image_right' => 'nullable|image|max:5120',
        ]);

        DB::beginTransaction();
        $uploadedCloudinaryIds = [];
        try {
            // Helper function update image
            $updateImage = function($file, $oldUrl) use (&$uploadedCloudinaryIds) {
                // Delete old file
                if (Str::contains($oldUrl, url('storage'))) {
                    $oldPath = str_replace(url('storage') . '/', '', $oldUrl);
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                } elseif (Str::contains($oldUrl, 'res.cloudinary.com')) {
                    $parts = explode('/upload/', $oldUrl);
                    if (count($parts) == 2) {
                        $publicIdWithExt = explode('/', $parts[1]);
                        array_shift($publicIdWithExt);
                        $publicIdPath = implode('/', $publicIdWithExt);
                        $publicId = pathinfo($publicIdPath, PATHINFO_DIRNAME) . '/' . pathinfo($publicIdPath, PATHINFO_FILENAME);
                        try {
                            cloudinary()->uploadApi()->destroy($publicId);
                        } catch (\Exception $e) {
                            Log::error("Failed to delete Cloudinary banner: " . $e->getMessage());
                        }
                    }
                }

                // Upload baru ke Cloudinary
                $uploadResult = cloudinary()->uploadApi()->upload($file->getRealPath(), [
                    'folder' => 'hspace/banners',
                    'format' => 'webp',
                    'transformation' => [
                        'width' => 1200,
                        'crop' => 'scale'
                    ]
                ]);
                $uploadedCloudinaryIds[] = $uploadResult['public_id'];
                return $uploadResult['secure_url'];
            };

            // Cek ada update gambar gak
            if ($request->hasFile('image_left')) {
                $banner->image_left = $updateImage($request->file('image_left'), $banner->image_left);
            }
            if ($request->hasFile('image_right')) {
                $banner->image_right = $updateImage($request->file('image_right'), $banner->image_right);
            }

            $banner->update([
                'title' => $request->title,
                'link_url' => $request->link_url,
                // position biasanya gak diubah biar gak ngerusak layout, atau bisa ditambahin kalau perlu
            ]);

            DB::commit();
            return response()->json(['message' => 'Banner updated', 'data' => $banner]);

        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedCloudinaryIds as $publicId) {
                try { cloudinary()->uploadApi()->destroy($publicId); } catch (\Exception $ex) {}
            }
            return response()->json(['message' => 'Failed to update banner: ' . $e->getMessage()], 500);
        }
    }

    // 5. ADMIN: Delete Banner (Clean up File)
    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);

        DB::beginTransaction();
        try {
            // Helper to delete physical/cloud file
            $deleteImage = function($url) {
                if (Str::contains($url, url('storage'))) {
                    $path = str_replace(url('storage') . '/', '', $url);
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                } elseif (Str::contains($url, 'res.cloudinary.com')) {
                    $parts = explode('/upload/', $url);
                    if (count($parts) == 2) {
                        $publicIdWithExt = explode('/', $parts[1]);
                        array_shift($publicIdWithExt);
                        $publicIdPath = implode('/', $publicIdWithExt);
                        $publicId = pathinfo($publicIdPath, PATHINFO_DIRNAME) . '/' . pathinfo($publicIdPath, PATHINFO_FILENAME);
                        try {
                            cloudinary()->uploadApi()->destroy($publicId);
                        } catch (\Exception $e) {
                            Log::error("Failed to delete Cloudinary banner: " . $e->getMessage());
                        }
                    }
                }
            };

            $deleteImage($banner->image_left);
            $deleteImage($banner->image_right);

            $banner->delete();
            DB::commit();

            return response()->json(['message' => 'Banner deleted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete banner: ' . $e->getMessage()], 500);
        }
    }

    // 6. ADMIN: Toggle Active Status
    public function toggleActive($id)
    {
        $banner = Banner::findOrFail($id);

        // Kalau di-aktifkan, matikan banner lain di posisi yang sama (Biar cuma 1 yg aktif per posisi)
        if (!$banner->is_active) {
            Banner::where('position', $banner->position)
                  ->where('id', '!=', $id)
                  ->update(['is_active' => false]);
        }

        $banner->update(['is_active' => !$banner->is_active]);

        return response()->json(['message' => 'Status updated', 'data' => $banner]);
    }
}
