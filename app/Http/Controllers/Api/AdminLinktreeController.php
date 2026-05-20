<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LinktreeSetting;
use App\Models\LinktreeLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AdminLinktreeController extends Controller
{
    public function getSettings()
    {
        $settings = LinktreeSetting::first();
        if (!$settings) {
            $settings = LinktreeSetting::create([
                'page_title' => 'HURTSSPACE',
                'page_subtitle' => 'Minimalist Streetwear Indonesia',
            ]);
        }

        $links = LinktreeLink::orderBy('sort_order', 'asc')->get();

        return response()->json([
            'settings' => $settings,
            'links' => $links,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $settings = LinktreeSetting::first();
        if (!$settings) {
            $settings = new LinktreeSetting();
        }

        $request->validate([
            'page_title' => 'required|string',
            'page_subtitle' => 'nullable|string',
            'background_type' => 'required|in:color,image',
            'background_color' => 'required|string',
            'page_text_color' => 'required|string',
            'button_bg_color' => 'required|string',
            'button_text_color' => 'required|string',
            'button_border_color' => 'required|string',
            'button_style' => 'required|in:solid,outline,glass',
            'logo' => 'nullable|image|max:5120',
            'background_image' => 'nullable|image|max:10240',
        ]);

        $manager = new ImageManager(new Driver());

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $image = $manager->read($file->getRealPath());
            $image->scale(width: 400); // Resize logo
            $filename = 'linktree_logo_' . Str::random(10) . '_' . time() . '.webp';
            Storage::disk('public')->put('linktree/' . $filename, (string) $image->toWebp(80));
            
            // Delete old logo
            if ($settings->logo) {
                $oldPath = str_replace(url('storage') . '/', '', $settings->logo);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $settings->logo = url('storage/linktree/' . $filename);
        }

        if ($request->hasFile('background_image')) {
            $file = $request->file('background_image');
            $image = $manager->read($file->getRealPath());
            $image->scale(width: 1200); // Resize bg
            $filename = 'linktree_bg_' . Str::random(10) . '_' . time() . '.webp';
            Storage::disk('public')->put('linktree/' . $filename, (string) $image->toWebp(80));

            // Delete old background
            if ($settings->background_image) {
                $oldPath = str_replace(url('storage') . '/', '', $settings->background_image);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $settings->background_image = url('storage/linktree/' . $filename);
        }

        $settings->page_title = $request->page_title;
        $settings->page_subtitle = $request->page_subtitle;
        $settings->background_type = $request->background_type;
        $settings->background_color = $request->background_color;
        $settings->page_text_color = $request->page_text_color;
        $settings->button_bg_color = $request->button_bg_color;
        $settings->button_text_color = $request->button_text_color;
        $settings->button_border_color = $request->button_border_color;
        $settings->button_style = $request->button_style;
        $settings->save();

        return response()->json(['message' => 'Settings updated successfully', 'data' => $settings]);
    }

    public function storeLink(Request $request)
    {
        $request->validate([
            'icon' => 'required|string',
            'label' => 'required|string',
            'url' => 'required|string',
            'custom_icon' => 'nullable|image|max:2048',
        ]);

        $customIconUrl = null;
        if ($request->hasFile('custom_icon')) {
            $manager = new ImageManager(new Driver());
            $file = $request->file('custom_icon');
            $image = $manager->read($file->getRealPath());
            $image->scale(width: 100);
            $filename = 'linktree_icon_' . Str::random(10) . '_' . time() . '.webp';
            Storage::disk('public')->put('linktree/' . $filename, (string) $image->toWebp(80));
            $customIconUrl = url('storage/linktree/' . $filename);
        }

        $maxOrder = LinktreeLink::max('sort_order');

        $link = LinktreeLink::create([
            'icon' => $request->icon,
            'custom_icon' => $customIconUrl,
            'label' => $request->label,
            'url' => $request->url,
            'sort_order' => $maxOrder !== null ? $maxOrder + 1 : 0,
            'is_active' => true,
        ]);

        return response()->json(['message' => 'Link created successfully', 'data' => $link]);
    }

    public function updateLink(Request $request, $id)
    {
        $link = LinktreeLink::findOrFail($id);

        $request->validate([
            'icon' => 'required|string',
            'label' => 'required|string',
            'url' => 'required|string',
            'custom_icon' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('custom_icon')) {
            $manager = new ImageManager(new Driver());
            $file = $request->file('custom_icon');
            $image = $manager->read($file->getRealPath());
            $image->scale(width: 100);
            $filename = 'linktree_icon_' . Str::random(10) . '_' . time() . '.webp';
            Storage::disk('public')->put('linktree/' . $filename, (string) $image->toWebp(80));
            
            if ($link->custom_icon) {
                $oldPath = str_replace(url('storage') . '/', '', $link->custom_icon);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $link->custom_icon = url('storage/linktree/' . $filename);
        } elseif ($request->has('remove_custom_icon') && $request->remove_custom_icon == 'true') {
            if ($link->custom_icon) {
                $oldPath = str_replace(url('storage') . '/', '', $link->custom_icon);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $link->custom_icon = null;
        }

        $link->icon = $request->icon;
        $link->label = $request->label;
        $link->url = $request->url;
        $link->save();

        return response()->json(['message' => 'Link updated successfully', 'data' => $link]);
    }

    public function destroyLink($id)
    {
        $link = LinktreeLink::findOrFail($id);
        
        if ($link->custom_icon) {
            $oldPath = str_replace(url('storage') . '/', '', $link->custom_icon);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $link->delete();

        return response()->json(['message' => 'Link deleted successfully']);
    }

    public function toggleLinkActive($id)
    {
        $link = LinktreeLink::findOrFail($id);
        $link->is_active = !$link->is_active;
        $link->save();

        return response()->json(['message' => 'Link status toggled', 'data' => $link]);
    }

    public function reorderLinks(Request $request)
    {
        $request->validate([
            'links' => 'required|array',
            'links.*.id' => 'required|integer|exists:linktree_links,id',
            'links.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->links as $item) {
            LinktreeLink::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['message' => 'Links reordered successfully']);
    }
}
