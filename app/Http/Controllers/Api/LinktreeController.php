<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LinktreeSetting;
use App\Models\LinktreeLink;

class LinktreeController extends Controller
{
    public function index()
    {
        $settings = LinktreeSetting::first() ?? [
            'page_title' => 'HURTSSPACE',
            'page_subtitle' => 'Minimalist Streetwear Indonesia',
            'background_type' => 'color',
            'background_color' => '#0a0a0a',
            'page_text_color' => '#ffffff',
            'button_bg_color' => '#ffffff',
            'button_text_color' => '#000000',
            'button_border_color' => '#333333',
            'button_style' => 'glass',
        ];

        $links = LinktreeLink::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->get();

        return response()->json([
            'settings' => $settings,
            'links' => $links,
        ]);
    }
}
