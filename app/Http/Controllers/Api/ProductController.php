<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    // 1. GET ALL PRODUCTS
    public function index(Request $request)
    {
        $query = Product::with(['category', 'images', 'variants']);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->latest()->paginate(10);

        return response()->json([
            'message' => 'List produk berhasil diambil',
            'data' => $products
        ]);
    }

    // 2. GET SINGLE PRODUCT
    public function show($slug)
    {
        $product = Product::with(['category', 'images', 'variants'])
            ->where('slug', $slug)
            ->first();

        if (!$product) {
            return response()->json([
                'message' => 'Waduh, Produk tidak ditemukan G! Coba cek linknya lagi.'
            ], 404);
        }

        return response()->json([
            'message' => 'Detail produk ditemukan',
            'data' => $product
        ]);
    }

    // 3. CREATE PRODUCT (Admin Only)
    public function store(Request $request)
    {
        // Validasi Manual biar pesan errornya bisa Custom Bahasa Gaul
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id', // Harus ada di tabel categories
            'description' => 'required',
            'price' => 'required|numeric|min:1000',
            'weight' => 'required|integer|min:1',
            'sizes' => 'required|array',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            // Custom Messages
            'required' => 'Kolom :attribute wajib diisi ya G!',
            'numeric' => 'Kolom :attribute harus berupa angka.',
            'category_id.exists' => 'Category ID tidak valid (Pastikan ID kategori ada di database).',
            'image.image' => 'File harus berupa gambar.',
            'image.max' => 'Ukuran gambar maksimal 2MB.',
        ]);

        // Kalau validasi gagal, balikin JSON 422
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Data tidak valid, cek lagi isian lo.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Upload Gambar
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        // Simpan Produk
        $product = Product::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . time(),
            'category_id' => $request->category_id,
            'description' => $request->description,
            'price' => $request->price,
            'weight' => $request->weight,
            'is_collab' => $request->boolean('is_collab'),
        ]);

        // Simpan Gambar
        if ($imagePath) {
            $product->images()->create([
                'image_url' => url('storage/' . $imagePath),
                'is_primary' => true
            ]);
        }

        // Simpan Varian Size
        foreach ($request->sizes as $size) {
            $product->variants()->create([
                'size' => $size,
                'stock' => 10, // Default stock
            ]);
        }

        return response()->json([
            'message' => 'Mantap! Produk berhasil dibuat.',
            'data' => $product->load('variants', 'images')
        ], 201);
    }

    // 4. DELETE PRODUCT (Admin Only)
    public function destroy($id)
    {
        // Cari pake find (bukan findOrFail) biar bisa kita custom errornya
        $product = Product::find($id);

        // Kalau produk gak ketemu (udah dihapus atau emang ga ada)
        if (!$product) {
            return response()->json([
                'message' => 'Error 404: Produk tidak ditemukan atau sudah dihapus sebelumnya.'
            ], 404);
        }

        // Hapus file gambar dari storage biar gak nyampah (Optional tapi bagus)
        // $product->images->each(function($img) { ...logic hapus file... });

        $product->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus dari muka bumi.'
        ], 200);
    }
}
