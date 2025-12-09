<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'Categories retrieved',
            'data' => Category::all() // Kirim semua data kategori (id & name)
        ]);
    }
}
