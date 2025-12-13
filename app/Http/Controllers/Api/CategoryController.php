<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    // 1. PUBLIC: List Semua Kategori
    public function index()
    {
        // Urutkan A-Z biar rapi
        $categories = Category::orderBy('name', 'asc')->get();
        return response()->json(['data' => $categories]);
    }

    // 2. ADMIN: Buat Kategori Baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ], [
            'name.unique' => 'Kategori ini udah ada, G!'
        ]);

        $category = Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ]);

        return response()->json([
            'message' => 'Kategori berhasil dibuat!',
            'data' => $category
        ], 201);
    }

    // 3. ADMIN: Update Kategori
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            // Ignore ID saat cek unique biar gak error kalau nama gak diganti
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ]);

        return response()->json([
            'message' => 'Kategori berhasil diupdate!',
            'data' => $category
        ]);
    }

    // 4. ADMIN: Hapus Kategori (Safe Delete)
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Cek apakah ada produk yang pake kategori ini?
        // Asumsi relasi di model Category adalah function products()
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Gagal hapus! Masih ada produk di kategori ini.'
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus!']);
    }
}
