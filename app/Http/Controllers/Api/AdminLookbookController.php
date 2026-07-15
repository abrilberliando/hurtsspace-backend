<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lookbook;
use App\Models\LookbookItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AdminLookbookController extends Controller
{
    // 1. List Lookbook
    public function index()
    {
        $lookbooks = Lookbook::with('items')->latest()->get();
        return response()->json(['data' => $lookbooks]);
    }

    // 2. Save New Lookbook (+ Hotspots)
    public function store(Request $request)
    {
        // 1. Change items validation to 'json' (sent as JSON string)
        $request->validate([
            'title' => 'nullable|string',
            'image' => 'required|image|max:3072',
            'items' => 'required|json',
        ]);

        DB::beginTransaction();
        try {
            // Upload Gambar ke Cloudinary
            $uploadResult = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath(), [
                'folder' => 'hspace/lookbooks',
                'format' => 'webp',
            ]);

            // Bikin Header Lookbook
            $lookbook = Lookbook::create([
                'title' => $request->title,
                'image_url' => $uploadResult['secure_url'],
            ]);

            // 2. Decode JSON String jadi Array PHP
            $items = json_decode($request->items, true);

            // Save Hotspot Points
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
            if (isset($uploadResult)) {
                try { cloudinary()->uploadApi()->destroy($uploadResult['public_id']); } catch (\Exception $ex) {}
            }
            return response()->json(['message' => 'Failed to save: ' . $e->getMessage()], 500);
        }
    }

    // 3. Delete Lookbook
    public function destroy($id)
    {
        $lookbook = Lookbook::findOrFail($id);
        
        // Delete image file
        if ($lookbook->image_url) {
            if (Str::contains($lookbook->image_url, url('storage'))) {
                $path = str_replace(url('storage') . '/', '', $lookbook->image_url);
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            } elseif (Str::contains($lookbook->image_url, 'res.cloudinary.com')) {
                $parts = explode('/upload/', $lookbook->image_url);
                if (count($parts) == 2) {
                    $publicIdWithExt = explode('/', $parts[1]);
                    array_shift($publicIdWithExt);
                    $publicIdPath = implode('/', $publicIdWithExt);
                    $publicId = pathinfo($publicIdPath, PATHINFO_DIRNAME) . '/' . pathinfo($publicIdPath, PATHINFO_FILENAME);
                    try {
                        cloudinary()->uploadApi()->destroy($publicId);
                    } catch (\Exception $e) {
                        Log::error("Failed to delete Cloudinary lookbook image: " . $e->getMessage());
                    }
                }
            }
        }
        
        $lookbook->delete();
        return response()->json(['message' => 'Lookbook deleted']);
    }
}
