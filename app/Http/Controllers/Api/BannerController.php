<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    // 1. PUBLIC: Ambil SEMUA Banner yang Statusnya AKTIF
    // Nanti Frontend yang misahin mana Posisi 1 (Hero), mana Posisi 2 (Tengah)
    public function getActive()
    {
        $banners = Banner::where('is_active', true)->get();

        return response()->json(['data' => $banners]);
    }

    // 2. ADMIN: List Semua Banner (Aktif & Non-Aktif)
    public function index()
    {
        return response()->json(['data' => Banner::latest()->get()]);
    }

    // 3. ADMIN: Upload Banner Baru
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'position' => 'required|in:1,2', // 👈 Validasi Posisi (1 atau 2)
            'image_left' => 'required|image|max:10240', // Max 2MB
            'image_right' => 'required|image|max:10240',
            'link_url' => 'required|string',
        ]);

        // Upload Gambar ke Storage
        $pathLeft = $request->file('image_left')->store('banners', 'public');
        $pathRight = $request->file('image_right')->store('banners', 'public');

        // Simpan ke Database
        $banner = Banner::create([
            'title' => $request->title,
            'position' => $request->position,
            'image_left' => url('storage/' . $pathLeft),
            'image_right' => url('storage/' . $pathRight),
            'link_url' => $request->link_url,
            'is_active' => true // Default langsung aktif
        ]);

        return response()->json(['message' => 'Banner created', 'data' => $banner], 201);
    }

    // 4. ADMIN: Hapus Banner
    public function destroy($id)
    {
        $banner = Banner::findOrFail($id);

        // Optional: Hapus file fisik dari storage biar hemat space
        // ... logic hapus file ...

        $banner->delete();
        return response()->json(['message' => 'Banner deleted']);
    }

    // 5. ADMIN: Toggle Active Status
    public function toggleActive($id)
    {
        $banner = Banner::findOrFail($id);

        // Logic model event 'booted' -> 'saving' akan otomatis jalan disini
        // Jadi kalau di-set true, banner lain di posisi yang SAMA otomatis jadi false
        $banner->update(['is_active' => !$banner->is_active]);

        return response()->json(['message' => 'Status updated', 'data' => $banner]);
    }
}
