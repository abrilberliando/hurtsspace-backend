<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeroSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class HeroSectionController extends Controller
{
    // 1. PUBLIC: Ambil Data Hero buat Homepage
    public function show()
    {
        // Ambil data pertama, kalau gak ada bikin baru pake default
        $hero = HeroSection::firstOrCreate(
            ['id' => 1],
            [
                // Default value kalau database masih kosong
                'title' => '',
                // Gambar default sementara (pake array 1 elemen)
                'background_images' => [
                    'https://images.unsplash.com/photo-1523396870179-16a196759575?q=80&w=1920&auto=format&fit=crop'
                ]
            ]
        );

        // Logik buat handle kompatibilitas kalau sebelumnya cuma punya 'background_image' string
        // Note: Field background_image harus dihapus dari database kalau mau bersih
        if (!is_array($hero->background_images) && $hero->background_image) {
             $hero->background_images = [$hero->background_image];
        }

        return response()->json(['data' => $hero]);
    }

    // 2. ADMIN: Update Data Hero (SUPPORT SLIDER)
    public function update(Request $request)
    {
        // Kita selalu update data dengan ID 1
        $hero = HeroSection::findOrFail(1);

        // VALIDASI: Validasi 5 slot gambar
        $validationRules = [
            'subtitle' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'button_text' => 'nullable|string|max:50',
            'button_link' => 'nullable|string|max:255',
        ];

        // Validasi untuk 5 gambar, bisa nullable
        for ($i = 0; $i < 5; $i++) {
            $validationRules["background_image_{$i}"] = 'nullable|image|max:3072';
            $validationRules["existing_image_{$i}"] = 'nullable|url|max:255';
        }

        $request->validate($validationRules);

        DB::beginTransaction();
        $uploadedCloudinaryIds = [];
        try {
            // LOGIC: Kumpulkan 5 URL gambar
            $newImageUrls = [];

            // Ambil array URL gambar lama untuk dihapus nanti
            $oldImageUrls = is_array($hero->background_images) ? $hero->background_images : [];

            $imagesToDelete = array_filter($oldImageUrls, function($url) {
                // Hanya proses string yang bukan link eksternal untuk dihapus
                return is_string($url) && !str_contains($url, 'http');
            });

            for ($i = 0; $i < 5; $i++) {
                // 1. Cek kalau ada file gambar baru diupload
                if ($request->hasFile("background_image_{$i}")) {
                    $uploadResult = cloudinary()->uploadApi()->upload($request->file("background_image_{$i}")->getRealPath(), [
                        'folder' => 'hspace/hero',
                        'format' => 'webp',
                        'transformation' => [
                            'width' => 1920,
                            'crop' => 'scale'
                        ]
                    ]);
                    $uploadedCloudinaryIds[] = $uploadResult['public_id'];
                    $url = $uploadResult['secure_url'];
                    $newImageUrls[] = $url;

                    // Hapus URL ini dari list yang akan dihapus, karena sudah diganti/diupload
                    $imagesToDelete = array_diff($imagesToDelete, [$url]);
                }
                // 2. Cek kalau ada URL gambar lama yang dipertahankan
                else if ($request->filled("existing_image_{$i}")) {
                    $url = $request->input("existing_image_{$i}");
                    $newImageUrls[] = $url;

                    // Hapus URL ini dari list yang akan dihapus
                    $imagesToDelete = array_diff($imagesToDelete, [$url]);
                }
            }

            // Hapus file lama yang tidak dipakai lagi (karena dihapus atau diganti)
            foreach ($imagesToDelete as $url) {
                if (is_string($url)) { // Filter lagi agar lebih aman
                    if (Str::contains($url, url('storage/'))) {
                        // Ambil path relatif
                        $oldPath = str_replace(url('storage/'), '', $url);
                        if (Storage::disk('public')->exists($oldPath)) {
                            Storage::disk('public')->delete($oldPath);
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
                                Log::error("Failed to delete Cloudinary hero image: " . $e->getMessage());
                            }
                        }
                    }
                }
            }

            // Ambil data non-file yang diizinkan untuk update
            $dataToUpdate = $request->only(['subtitle', 'title', 'description', 'button_text', 'button_link']);

            // Simpan array URL baru ke database
            $dataToUpdate['background_images'] = $newImageUrls;

            // 👇 PERBAIKAN: Gunakan $dataToUpdate yang sudah bersih
            $hero->update($dataToUpdate);

            DB::commit();
            return response()->json(['message' => 'Hero section updated!', 'data' => $hero]);
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedCloudinaryIds as $publicId) {
                try { cloudinary()->uploadApi()->destroy($publicId); } catch (\Exception $ex) {}
            }
            return response()->json(['message' => 'Gagal update hero section: ' . $e->getMessage()], 500);
        }
    }
}
