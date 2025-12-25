<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VideoBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // 👈 Pake Storage Lokal
use Illuminate\Support\Str;

class VideoBannerController extends Controller
{
    // 1. PUBLIC: Get Active Video
    public function getActive()
    {
        // Ambil video yang aktif paling baru
        $video = VideoBanner::where('is_active', true)->latest()->first();

        // Safety Net HTTPS buat Production (Biar gak Mixed Content error)
        if ($video && app()->environment('production')) {
            $video->video_url = str_replace('http://', 'https://', $video->video_url);
        }

        return response()->json(['data' => $video]);
    }

    // 2. ADMIN: List Semua Video
    public function index()
    {
        return response()->json(['data' => VideoBanner::latest()->get()]);
    }

    // 3. ADMIN: Store (Upload Local)
    public function store(Request $request)
    {
        // Validasi Video: Max 50MB (sesuaikan config php.ini lo ya: upload_max_filesize)
        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime|max:7000',
            'title' => 'nullable|string',
            'link_url' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Upload Video ke Local Storage
            // Folder: storage/app/public/videos
            $file = $request->file('video');
            $filename = 'video_' . Str::random(10) . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('videos', $filename, 'public');

            $video = VideoBanner::create([
                'title' => $request->title,
                'description' => $request->description, // Opsional di migration
                'video_url' => url('storage/' . $path), // Simpan URL lengkap
                'link_url' => $request->link_url,
                'is_active' => true // Default langsung aktif
            ]);

            // Opsional: Matikan video lain biar cuma 1 yang aktif di Homepage
            // VideoBanner::where('id', '!=', $video->id)->update(['is_active' => false]);

            DB::commit();
            return response()->json(['message' => 'Video uploaded successfully', 'data' => $video], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            // Hapus file kalau DB gagal, biar gak jadi file hantu
            if (isset($path)) Storage::disk('public')->delete($path);

            return response()->json(['message' => 'Gagal upload: ' . $e->getMessage()], 500);
        }
    }

    // 4. ADMIN: Update
    public function update(Request $request, $id)
    {
        $videoBanner = VideoBanner::findOrFail($id);

        DB::beginTransaction();
        try {
            if ($request->hasFile('video')) {
                // 1. Hapus Video Lama (Biar hemat storage)
                if (Str::contains($videoBanner->video_url, url('storage'))) {
                    $oldPath = str_replace(url('storage') . '/', '', $videoBanner->video_url);
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }

                // 2. Upload Baru
                $file = $request->file('video');
                $filename = 'video_' . Str::random(10) . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('videos', $filename, 'public');

                $videoBanner->video_url = url('storage/' . $path);
            }

            $videoBanner->update([
                'title' => $request->title,
                'description' => $request->description,
                'link_url' => $request->link_url,
            ]);

            DB::commit();
            return response()->json(['message' => 'Video updated', 'data' => $videoBanner]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal update: ' . $e->getMessage()], 500);
        }
    }

    // 5. ADMIN: Delete
    public function destroy($id)
    {
        $video = VideoBanner::findOrFail($id);

        // Hapus file fisik jika ada di storage lokal
        if (Str::contains($video->video_url, url('storage'))) {
            $path = str_replace(url('storage') . '/', '', $video->video_url);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $video->delete();
        return response()->json(['message' => 'Video deleted']);
    }

    // 6. ADMIN: Toggle Active
    public function toggleActive($id)
    {
        $video = VideoBanner::findOrFail($id);
        $video->update(['is_active' => !$video->is_active]);
        return response()->json(['message' => 'Status updated']);
    }
}
