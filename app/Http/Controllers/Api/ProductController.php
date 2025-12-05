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
        // 👇 TAMBAH 'category' DISINI BIAR KEBACA
        $products = Product::with(['images', 'variants', 'category'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['data' => $products]);
    }

    // 2. GET SINGLE PRODUCT
    public function show($id)
    {
        // 👇 TAMBAH 'category' DISINI JUGA
        $product = Product::with(['images', 'variants', 'category'])->findOrFail($id);

        return response()->json(['data' => $product]);
    }
    // 3. CREATE PRODUCT (Admin Only) - Multi-Image Ready
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
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048', // Setiap file di array harus gambar
        ], [
            // Custom Messages
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
            ]);

            // Simpan MULTIPLE IMAGES
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    // Simpan file, lalu catat URL-nya di database
                    $imagePath = $image->store('products', 'public');

                    $product->images()->create([
                        'image_url' => url('storage/' . $imagePath),
                        // Gambar pertama (index 0) jadi gambar utama
                        'is_primary' => $index === 0
                    ]);
                }
            }

            // Simpan Variants (Size & Stok Default)
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

    // 4. UPDATE PRODUCT (Admin Only) - Paling kompleks, handle gambar lama & baru
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

            'images' => 'nullable|array', // Gambar baru (opsional)
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',

            'existing_images' => 'nullable|array', // URL gambar lama yang dipertahankan
        ], [
            'required' => 'Kolom :attribute wajib diisi ya G!',
            'numeric' => 'Kolom :attribute harus berupa angka.',
            'category_id.exists' => 'Category ID tidak valid.',
            'sizes.required' => 'Wajib pilih minimal 1 size.',
            'images.*.image' => 'File harus berupa gambar (jpeg, png, jpg).',
            'images.*.max' => 'Ukuran setiap gambar maksimal 2MB.',
        ]);

        // Cek total gambar (lama + baru)
        $totalImages = count($request->input('existing_images', [])) + count($request->file('images', []));
        if ($totalImages === 0) {
            return response()->json(['message' => 'Wajib ada minimal 1 foto produk.', 'errors' => ['images' => ['Wajib ada minimal 1 foto produk.']]], 422);
        }

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data tidak valid, cek lagi isian lo.',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // 1. Update Data Dasar Produk
            $product->update([
                'name' => $request->name,
                // Kita ganti slug-nya biar unik meskipun namanya sama
                'slug' => \Illuminate\Support\Str::slug($request->name) . '-' . time(),
                'category_id' => $request->category_id,
                'description' => $request->description,
                'price' => $request->price,
                'weight' => $request->weight,
                'is_collab' => $request->boolean('is_collab'),
            ]);

            // 2. Kelola Gambar (Hapus yang dibuang, tambah yang baru)
            $existingImagesToKeep = $request->input('existing_images', []);
            $imagesToDelete = $product->images->pluck('image_url')->diff($existingImagesToKeep);

            // Hapus gambar yang TIDAK dipertahankan dari DB
            if ($imagesToDelete->count() > 0) {
                $imagesToDelete->each(function ($url) {
                    // Ambil path relatif dari URL (misal: 'http://localhost:8000/storage/products/xxx.jpg' -> 'products/xxx.jpg')
                    $path = str_replace(url('storage') . '/', '', $url);
                    // Hapus dari Storage
                    Storage::disk('public')->delete($path);
                });
                // Hapus dari Database
                ProductImage::whereIn('image_url', $imagesToDelete)->delete();
            }

            // Tambahkan gambar BARU
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imagePath = $image->store('products', 'public');
                    $product->images()->create([
                        'image_url' => url('storage/' . $imagePath),
                        'is_primary' => false
                    ]);
                }
            }

            // Re-set Primary Image (Gambar pertama di list yang tersisa jadi Primary)
            $firstImage = $product->images()->orderBy('id', 'asc')->first();
            if ($firstImage && !$firstImage->is_primary) {
                $product->images()->update(['is_primary' => false]); // Reset semua
                $firstImage->update(['is_primary' => true]); // Set yang pertama
            }


            // 3. Kelola Varian/Size (Hapus semua lama, buat baru)
            $product->variants()->delete();
            foreach ($request->sizes as $size) {
                // Catatan: Ini mereset stock jadi 10. Admin harus edit manual stocknya.
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
            // Hapus gambar fisik dari storage
            $product->images->each(function($image) {
                $path = str_replace(url('storage') . '/', '', $image->image_url);
                Storage::disk('public')->delete($path);
            });

            // Hapus data produk (variants dan images terhapus otomatis karena 'cascade on delete')
            $product->delete();

            DB::commit();

            return response()->json(['message' => 'Produk berhasil dihapus!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menghapus produk: ' . $e->getMessage()], 500);
        }
    }
}
