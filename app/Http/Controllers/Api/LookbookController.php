<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lookbook;
use Illuminate\Http\Request;

class LookbookController extends Controller
{
    public function index()
    {
        // Ambil Lookbook beserta Item-nya (Hotspots) dan Produk terkait
        $lookbooks = Lookbook::with(['items.product.images'])->latest()->get();

        return response()->json([
            'message' => 'Lookbooks retrieved',
            'data' => $lookbooks
        ]);
    }

    public function show($id)
    {
        $lookbook = Lookbook::with(['items.product.images'])->findOrFail($id);

        return response()->json([
            'message' => 'Lookbook detail retrieved',
            'data' => $lookbook
        ]);
    }
}
