<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lookbook;
use App\Models\LookbookItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminLookbookController extends Controller
{
    // 1. List Lookbook
    public function index()
    {
        $lookbooks = Lookbook::with('items')->latest()->get();
        return response()->json(['data' => $lookbooks]);
    }

    // 2. Simpan Lookbook Baru (+ Hotspots)
    public function store(Request $request)
    {
        // 1. Ubah validasi items jadi 'json' (karena dikirim sebagai string JSON)
        $request->validate([
            'title' => 'required|string',
            'image' => 'required|image|max:2048',
            'items' => 'required|json',
        ]);

        DB::beginTransaction();
        try {
            // Upload Gambar
            $path = $request->file('image')->store('lookbooks', 'public');

            // Bikin Header Lookbook
            $lookbook = Lookbook::create([
                'title' => $request->title,
                'image_url' => url('storage/' . $path),
            ]);

            // 2. Decode JSON String jadi Array PHP
            $items = json_decode($request->items, true);

            // Simpan Titik-titik Hotspot
            foreach ($items as $item) {
                $lookbook->items()->create([
                    'product_id' => $item['product_id'],
                    'x_position' => $item['x'],
                    'y_position' => $item['y'],
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Lookbook created successfully', 'data' => $lookbook]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal simpan: ' . $e->getMessage()], 500);
        }
    }

    // 3. Hapus Lookbook
    public function destroy($id)
    {
        $lookbook = Lookbook::findOrFail($id);
        // Hapus file gambar (Optional, good practice)
        // ...
        $lookbook->delete();
        return response()->json(['message' => 'Lookbook deleted']);
    }
}
