<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BiteshipService;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    protected $biteship;

    // Inject Service tadi ke sini
    public function __construct(BiteshipService $biteship)
    {
        $this->biteship = $biteship;
    }

    // 1. Endpoint Cari Kota/Kecamatan
    public function searchArea(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:3' // Minimal 3 huruf biar gak berat
        ]);

        $result = $this->biteship->searchArea($request->query('query'));

        return response()->json($result);
    }

    // 2. Endpoint Cek Harga Ongkir
    public function checkCost(Request $request)
    {
        $request->validate([
            'destination_area_id' => 'required|string',
            'weight' => 'required|integer|min:100', // Minimal 100 gram
        ]);

        // Panggil Service buat nanya ke Biteship
        $result = $this->biteship->checkRates(
            $request->destination_area_id,
            $request->weight
        );

        return response()->json($result);
    }
}
