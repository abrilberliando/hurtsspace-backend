<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    // 1. GET ALL PRODUCTS
    public function index()
    {
        // 👇 Gambar diambil DENGAN URUTAN sort_order (1, 2, 3...)
        $products = Product::with(['images' => function ($query) {
            $query->orderBy('sort_order', 'asc');
        }, 'variants', 'category'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['data' => $products]);
    }

    // 2. GET SINGLE PRODUCT
    public function show($key)
    {
        // Cek apakah $key ini murni angka (ID) atau string (Slug)
        // Kita asumsikan ID itu numeric.

        $query = Product::with(['images' => function ($query) {
            $query->orderBy('sort_order', 'asc');
        }, 'variants', 'category']);

        if (is_numeric($key)) {
            // Kalau angka murni, cari by ID dulu.
            // Kalau gak ketemu by ID, baru cari by Slug (siapa tau slugnya emang angka doang wkwk)
            $product = $query->where('id', $key)->first();

            if (!$product) {
                $product = $query->where('slug', $key)->firstOrFail();
            }
        } else {
            // Kalau string (ada huruf/strip), LANGSUNG cari by SLUG.
            // Jangan cari by ID biar gak kena integer casting error.
            $product = $query->where('slug', $key)->firstOrFail();
        }

        return response()->json(['data' => $product]);
    }

    // 3. CREATE PRODUCT
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required',
            'price' => 'required|numeric|min:1000',
            'weight' => 'required|integer|min:1',
            'sizes' => 'required|array',
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:3072',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $product = Product::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name) . '-' . time(),
                'category_id' => $request->category_id,
                'description' => $request->description,
                'price' => $request->price,
                'weight' => $request->weight,
                'is_collab' => $request->boolean('is_collab'),
                'is_new_arrival' => true,
                'is_featured' => false,
            ]);

            // 👇 Simpan dengan sort_order saat create
            $uploadedCloudinaryIds = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    // Upload ke Cloudinary dan convert ke webp
                    $uploadResult = cloudinary()->uploadApi()->upload($image->getRealPath(), [
                        'folder' => 'hspace/products',
                        'format' => 'webp'
                    ]);
                    $uploadedCloudinaryIds[] = $uploadResult['public_id'];
                    
                    $product->images()->create([
                        'image_url' => $uploadResult['secure_url'],
                        'is_primary' => $index === 0,
                        'sort_order' => $index + 1 // Urutan 1, 2, 3...
                    ]);
                }
            }

            foreach ($request->sizes as $size) {
                $product->variants()->create(['size' => $size, 'stock' => 10]);
            }

            DB::commit();
            return response()->json(['message' => 'Produk berhasil dibuat!', 'data' => $product], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($uploadedCloudinaryIds)) {
                foreach ($uploadedCloudinaryIds as $publicId) {
                    try { cloudinary()->uploadApi()->destroy($publicId); } catch (\Exception $ex) {}
                }
            }
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // 4. UPDATE PRODUCT - LOGIC UTAMA SORTING 🌟
    public function update(Request $request, $id)
    {
        $product = Product::with('images', 'variants')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required',
            'price' => 'required|numeric|min:1000',
            'weight' => 'required|integer|min:1',
            'sizes' => 'required|array',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:3072',
            // 👇 Wajib ada image_order buat nentuin posisi kongkrit
            'image_order' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Data tidak valid.', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // A. Update Info Dasar
            $product->update([
                'name' => $request->name,
                'slug' => ($request->name !== $product->name) ? Str::slug($request->name) . '-' . time() : $product->slug,
                'category_id' => $request->category_id,
                'description' => $request->description,
                'price' => $request->price,
                'weight' => $request->weight,
                'is_collab' => $request->boolean('is_collab'),
            ]);

            // B. PROSES SORTING & DELETE
            $orderList = $request->input('image_order', []);

            // 1. Kumpulkan URL "Existing" yang masih dipake
            $existingUrlsToKeep = [];
            foreach ($orderList as $orderItem) {
                if (str_starts_with($orderItem, 'existing|')) {
                    $url = explode('|', $orderItem)[1];
                    $existingUrlsToKeep[] = $url;
                }
            }

            // 2. Hapus Foto di DB yang DIBUANG user
            $imagesToDelete = $product->images()->whereNotIn('image_url', $existingUrlsToKeep)->get();
            foreach ($imagesToDelete as $img) {
                if (Str::contains($img->image_url, url('storage'))) {
                    $relativePath = str_replace(url('storage') . '/', '', $img->image_url);
                    if (Storage::disk('public')->exists($relativePath)) {
                        Storage::disk('public')->delete($relativePath);
                    }
                } elseif (Str::contains($img->image_url, 'res.cloudinary.com')) {
                    // Coba extract public ID dari URL Cloudinary
                    $parts = explode('/upload/', $img->image_url);
                    if (count($parts) == 2) {
                        $publicIdWithExt = explode('/', $parts[1]);
                        array_shift($publicIdWithExt); // Remove version e.g. v1234567890
                        $publicIdPath = implode('/', $publicIdWithExt);
                        $publicId = pathinfo($publicIdPath, PATHINFO_DIRNAME) . '/' . pathinfo($publicIdPath, PATHINFO_FILENAME);
                        try {
                            cloudinary()->uploadApi()->destroy($publicId);
                        } catch (\Exception $e) {
                            Log::error("Failed to delete Cloudinary image: " . $e->getMessage());
                        }
                    }
                }
                $img->delete();
            }

            // 3. Upload Foto Baru (Tampung di array index)
            $uploadedNewFiles = []; // Map index upload -> Object Model
            $uploadedCloudinaryIds = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $uploadResult = cloudinary()->uploadApi()->upload($image->getRealPath(), [
                        'folder' => 'hspace/products',
                        'format' => 'webp'
                    ]);
                    $uploadedCloudinaryIds[] = $uploadResult['public_id'];
                    $uploadedNewFiles[$index] = $product->images()->create([
                        'image_url' => $uploadResult['secure_url'],
                        'is_primary' => false,
                        'sort_order' => 999
                    ]);
                }
            }

            // 4. FINAL SORTING LOOP 🌟
            foreach ($orderList as $index => $orderString) {
                $isPrimary = ($index === 0);
                $sortOrder = $index + 1;

                if (str_starts_with($orderString, 'existing|')) {
                    // Update Foto Lama: Assign sort_order dan primary
                    $url = explode('|', $orderString)[1];
                    $product->images()->where('image_url', $url)->update([
                        'sort_order' => $sortOrder,
                        'is_primary' => $isPrimary
                    ]);

                } elseif (str_starts_with($orderString, 'new|')) {
                    // Update Foto Baru: Ambil dari array yg baru diupload tadi
                    $fileIndex = (int) explode('|', $orderString)[1];

                    if (isset($uploadedNewFiles[$fileIndex])) {
                        $uploadedNewFiles[$fileIndex]->update([
                            'sort_order' => $sortOrder,
                            'is_primary' => $isPrimary
                        ]);
                    }
                }
            }

            // C. Handle Variants
            $oldVariants = $product->variants->pluck('stock', 'size')->toArray();
            $product->variants()->delete();
            foreach ($request->sizes as $size) {
                $stock = isset($oldVariants[$size]) ? $oldVariants[$size] : 10;
                $product->variants()->create(['size' => $size, 'stock' => $stock]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Produk berhasil diupdate!',
                'data' => $product->refresh()->load(['images' => function ($q) {
                    $q->orderBy('sort_order', 'asc'); // Return urut
                }, 'variants'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($uploadedCloudinaryIds)) {
                foreach ($uploadedCloudinaryIds as $publicId) {
                    try { cloudinary()->uploadApi()->destroy($publicId); } catch (\Exception $ex) {}
                }
            }
            Log::error("Update Product Error: " . $e->getMessage());
            return response()->json(['message' => 'Gagal update produk, server error.'], 500);
        }
    }

    // 5. DELETE PRODUCT
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        DB::beginTransaction();
        try {
            $product->images->each(function ($image) {
                if (Str::contains($image->image_url, url('storage'))) {
                    $path = str_replace(url('storage') . '/', '', $image->image_url);
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                } elseif (Str::contains($image->image_url, 'res.cloudinary.com')) {
                    $parts = explode('/upload/', $image->image_url);
                    if (count($parts) == 2) {
                        $publicIdWithExt = explode('/', $parts[1]);
                        array_shift($publicIdWithExt);
                        $publicIdPath = implode('/', $publicIdWithExt);
                        $publicId = pathinfo($publicIdPath, PATHINFO_DIRNAME) . '/' . pathinfo($publicIdPath, PATHINFO_FILENAME);
                        try {
                            cloudinary()->uploadApi()->destroy($publicId);
                        } catch (\Exception $e) {
                            Log::error("Failed to delete Cloudinary image: " . $e->getMessage());
                        }
                    }
                }
            });
            $product->delete();
            DB::commit();
            return response()->json(['message' => 'Produk berhasil dihapus!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menghapus produk: ' . $e->getMessage()], 500);
        }
    }

    // 6. GET FEATURED PRODUCTS
    public function getFeatured()
    {
        // 👇 Diurutkan berdasarkan sort_order untuk gambar
        $products = Product::with(['images' => function ($query) {
            $query->orderBy('sort_order', 'asc');
        }, 'category'])
            ->where('is_featured', true)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json(['data' => $products]);
    }

    // 7. TOGGLE FEATURED STATUS
    public function setFeatured($id)
    {
        $product = Product::findOrFail($id);

        if (!$product->is_featured) {
            $count = Product::where('is_featured', true)->count();
            if ($count >= 15) {
                return response()->json([
                    'message' => 'Slot Featured Penuh (Max 15), G! Hapus satu dulu biar bisa masuk.'
                ], 422);
            }
            $product->update(['is_featured' => true]);
            $msg = 'Produk berhasil jadi Featured!';
        } else {
            $product->update(['is_featured' => false]);
            $msg = 'Produk dihapus dari Featured.';
        }

        return response()->json(['message' => $msg, 'data' => $product]);
    }
}
