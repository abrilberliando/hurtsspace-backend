<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BiteshipService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $this->baseUrl = env('BITESHIP_BASE_URL', 'https://api.biteship.com/v1');
        $this->token = env('BITESHIP_API_TOKEN');
    }

    /**
     * 1. Cari Area (Kecamatan/Kota)
     * Biteship butuh "Area ID", bukan nama kota string biasa.
     */
    public function searchArea($query)
    {
        $response = Http::withToken($this->token)
            ->get($this->baseUrl . '/maps/areas', [
                'countries' => 'ID',
                'input' => $query,
                'type' => 'single' // Biar hasilnya spesifik
            ]);

        return $response->json();
    }

    /**
     * 2. Cek Ongkir (Rates)
     */
    public function checkRates($destinationAreaId, $weightInGram)
    {
        // Lokasi Gudang Hurtsspace (Lo harus set ini di .env nanti)
        $originAreaId = env('BITESHIP_ORIGIN_AREA_ID');

        $response = Http::withToken($this->token)
            ->post($this->baseUrl . '/rates/couriers', [
                'origin_area_id' => $originAreaId,
                'destination_area_id' => $destinationAreaId,
                'couriers' => 'jne,jnt,gosend', // Kurir yang mau lo pake
                'items' => [
                    [
                        'name' => 'Paket Hurtsspace',
                        'value' => 100000, // Dummy value buat asuransi
                        'weight' => $weightInGram,
                        'quantity' => 1
                    ]
                ]
            ]);

        return $response->json();
    }
}
