<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // 1. GET ALL PRODUCTS
    public function index()
    {
        $products = Product::with(['images', 'variants', 'category'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['data' => $products]);
    }

    // 2. GET SINGLE PRODUCT
    public function show($key)
    {
        // Cari produk dimana ID = $key ATAU Slug = $key
        // firstOrFail() bakal otomatis return 404 kalau gak ketemu
        $product = Product::with(['images', 'variants', 'category'])
            ->where('id', $key)
            ->orWhere('slug', $key)
            ->firstOrFail();

        return response()->json(['data' => $product]);
    }

    // 3. CREATE PRODUCT (Admin Only)
    public function store(Request $request)
    {
        // Validasi input
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required',
            'price' => 'required|numeric|min:1000',
            'weight' => 'required|integer|min:1',
            'sizes' => 'required|array',
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'required' => 'Kolom :attribute wajib diisi ya G!',
            'numeric' => 'Kolom :attribute harus berupa angka.',
            'category_id.exists' => 'Category ID tidak valid.',
            'images.required' => 'Wajib upload minimal 1 foto produk.',
            'images.*.image' => 'File harus berupa gambar (jpeg, png, jpg).',
            'images.*.max' => 'Ukuran setiap gambar maksimal 2MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data tidak valid, cek lagi isian lo.',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Simpan Data Produk
            $product = Product::create([
                'name' => $request->name,
                'slug' => \Illuminate\Support\Str::slug($request->name) . '-' . time(),
                'category_id' => $request->category_id,
                'description' => $request->description,
                'price' => $request->price,
                'weight' => $request->weight,
                'is_collab' => $request->boolean('is_collab'),
                'is_new_arrival' => true,
                'is_featured' => false, // Default false
            ]);

            // Simpan MULTIPLE IMAGES
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $imagePath = $image->store('products', 'public');

                    $product->images()->create([
                        'image_url' => url('storage/' . $imagePath),
                        'is_primary' => $index === 0
                    ]);
                }
            }

            // Simpan Variants
            foreach ($request->sizes as $size) {
                $product->variants()->create([
                    'size' => $size,
                    'stock' => 10,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Produk berhasil dibuat!',
                'data' => $product->load('variants', 'images')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal membuat produk: ' . $e->getMessage()], 500);
        }
    }

    // 4. UPDATE PRODUCT (Admin Only)
    public function update(Request $request, $id)
    {
        $product = Product::with('images', 'variants')->findOrFail($id);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required',
            'price' => 'required|numeric|min:1000',
            'weight' => 'required|integer|min:1',
            'sizes' => 'required|array',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'existing_images' => 'nullable|array',
        ]);

        $totalImages = count($request->input('existing_images', [])) + count($request->file('images', []));
        if ($totalImages === 0) {
            return response()->json(['message' => 'Wajib ada minimal 1 foto produk.'], 422);
        }

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors()
            ], 422);
        }

        // 👇 VALIDASI FEATURED (Jika admin nge-set dari form edit)
        if ($request->has('is_featured')) {
            $shouldBeFeatured = $request->boolean('is_featured');
            // Jika mau jadi featured DAN sebelumnya belum featured
            if ($shouldBeFeatured && !$product->is_featured) {
                $count = Product::where('is_featured', true)->count();
                if ($count >= 15) {
                    return response()->json(['message' => 'Slot Featured Penuh (Max 15), G! Hapus satu dulu.'], 422);
                }
            }
        }

        DB::beginTransaction();

        try {
            // 1. Update Data Dasar
            $product->update([
                'name' => $request->name,
                'slug' => \Illuminate\Support\Str::slug($request->name) . '-' . time(),
                'category_id' => $request->category_id,
                'description' => $request->description,
                'price' => $request->price,
                'weight' => $request->weight,
                'is_collab' => $request->boolean('is_collab'),
                // Update is_featured kalau dikirim, kalau nggak pakai value lama
                'is_featured' => $request->has('is_featured') ? $request->boolean('is_featured') : $product->is_featured,
            ]);

            // 2. Kelola Gambar
            $existingImagesToKeep = $request->input('existing_images', []);
            $imagesToDelete = $product->images->pluck('image_url')->diff($existingImagesToKeep);

            if ($imagesToDelete->count() > 0) {
                $imagesToDelete->each(function ($url) {
                    $path = str_replace(url('storage') . '/', '', $url);
                    Storage::disk('public')->delete($path);
                });
                ProductImage::whereIn('image_url', $imagesToDelete)->delete();
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imagePath = $image->store('products', 'public');
                    $product->images()->create([
                        'image_url' => url('storage/' . $imagePath),
                        'is_primary' => false
                    ]);
                }
            }

            // Re-set Primary
            $firstImage = $product->images()->orderBy('id', 'asc')->first();
            if ($firstImage && !$firstImage->is_primary) {
                $product->images()->update(['is_primary' => false]);
                $firstImage->update(['is_primary' => true]);
            }

            // 3. Kelola Varian
            $product->variants()->delete();
            foreach ($request->sizes as $size) {
                $product->variants()->create(['size' => $size, 'stock' => 10]);
            }

            DB::commit();

            return response()->json(['message' => 'Produk berhasil diupdate!', 'data' => $product->load('variants', 'images')]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal mengupdate produk: ' . $e->getMessage()], 500);
        }
    }

    // 5. DELETE PRODUCT (Admin Only)
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        DB::beginTransaction();
        try {
            $product->images->each(function($image) {
                $path = str_replace(url('storage') . '/', '', $image->image_url);
                Storage::disk('public')->delete($path);
            });
            $product->delete();
            DB::commit();
            return response()->json(['message' => 'Produk berhasil dihapus!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menghapus produk: ' . $e->getMessage()], 500);
        }
    }

    // 👇 6. GET FEATURED PRODUCTS (Udah Support Banyak)
    public function getFeatured()
    {
        // Ambil SEMUA produk featured, bukan cuma satu
        $products = Product::with(['images', 'category'])
            ->where('is_featured', true)
            ->orderBy('updated_at', 'desc') // Yang baru di-update statusnya di atas
            ->get();

        // Kalau kosong melompong, opsional: return kosong atau return top products
        // Disini gue balikin array kosong aja kalau gak ada, biar frontend gak bingung

        return response()->json(['data' => $products]);
    }

    // 👇 7. TOGGLE FEATURED STATUS (Logic Squad Max 15)
    public function setFeatured($id)
    {
        $product = Product::findOrFail($id);

        // Logic TOGGLE:
        // Kalau status sekarang TRUE -> mau jadi FALSE (Un-feature) -> Selalu boleh.
        // Kalau status sekarang FALSE -> mau jadi TRUE (Feature) -> Cek slot dulu.

        if (!$product->is_featured) {
            // Mau mengaktifkan, cek dulu slotnya
            $count = Product::where('is_featured', true)->count();

            if ($count >= 15) {
                return response()->json([
                    'message' => 'Slot Featured Penuh (Max 15), G! Hapus satu dulu biar bisa masuk.'
                ], 422);
            }

            $product->update(['is_featured' => true]);
            $msg = 'Produk berhasil jadi Featured!';
        } else {
            // Mau menonaktifkan
            $product->update(['is_featured' => false]);
            $msg = 'Produk dihapus dari Featured.';
        }

        return response()->json(['message' => $msg, 'data' => $product]);
    }
}
