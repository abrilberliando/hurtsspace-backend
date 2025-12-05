<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeroSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
                'title' => 'REDEFINE YOUR STREETWEAR.',
                // Gambar default sementara (bisa diganti nanti di admin)
                'background_image' => 'https://images.unsplash.com/photo-1523396870179-16a196759575?q=80&w=1920&auto=format&fit=crop'
            ]
        );

        return response()->json(['data' => $hero]);
    }

    // 2. ADMIN: Update Data Hero
    public function update(Request $request)
    {
        // Kita selalu update data dengan ID 1
        $hero = HeroSection::findOrFail(1);

        $request->validate([
            'background_image' => 'nullable|image|max:4096', // Max 4MB biar tajem
            'subtitle' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'button_text' => 'required|string|max:50',
            'button_link' => 'required|string|max:255',
        ]);

        $data = $request->only(['subtitle', 'title', 'description', 'button_text', 'button_link']);

        // Cek kalau admin upload gambar baru
        if ($request->hasFile('background_image')) {
            // Hapus gambar lama kalau bukan link eksternal
            if ($hero->background_image && !str_contains($hero->background_image, 'http')) {
                // Ambil path relatif dari URL lengkap
                $oldPath = str_replace(url('storage/'), '', $hero->background_image);
                Storage::disk('public')->delete($oldPath);
            }

            // Upload gambar baru
            $path = $request->file('background_image')->store('hero', 'public');
            $data['background_image'] = url('storage/' . $path);
        }

        $hero->update($data);

        return response()->json(['message' => 'Hero section updated!', 'data' => $hero]);
    }
}
