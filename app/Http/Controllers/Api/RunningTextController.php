<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RunningText;
use Illuminate\Http\Request;

class RunningTextController extends Controller
{
    // 1. PUBLIC: Ambil yang AKTIF saja
    public function getActive()
    {
        $texts = RunningText::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->latest()
            ->get();

        return response()->json(['data' => $texts]);
    }

    // 2. ADMIN: List Semua
    public function index()
    {
        return response()->json(['data' => RunningText::orderBy('sort_order', 'asc')->get()]);
    }

    // 3. ADMIN: Store
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:255',
            'link_url' => 'nullable|string',
        ]);

        $text = RunningText::create([
            // 👇 FIX: Pakai input() karena 'content' adalah properti protected di class Request
            'content' => $request->input('content'),
            'link_url' => $request->input('link_url'),
            'is_active' => true,
            'sort_order' => 0
        ]);

        return response()->json(['message' => 'Created', 'data' => $text], 201);
    }

    // 4. ADMIN: Update
    public function update(Request $request, $id)
    {
        $text = RunningText::findOrFail($id);

        $request->validate([
            'content' => 'required|string',
        ]);

        // Kalau update pake all() aman, karena dia ngambil array input
        $text->update($request->all());
        return response()->json(['message' => 'Updated', 'data' => $text]);
    }

    // 5. ADMIN: Delete
    public function destroy($id)
    {
        RunningText::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // 6. ADMIN: Toggle Active
    public function toggleActive($id)
    {
        $text = RunningText::findOrFail($id);
        $text->update(['is_active' => !$text->is_active]);
        return response()->json(['message' => 'Status updated']);
    }
}
