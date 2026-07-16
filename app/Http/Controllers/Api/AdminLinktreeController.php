<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LinktreeSetting;
use App\Models\LinktreeLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdminLinktreeController extends Controller
{
    private function deleteImage($url)
    {
        if (!$url) return;
        
        if (Str::contains($url, url('storage'))) {
            $path = str_replace(url('storage') . '/', '', $url);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } elseif (Str::contains($url, 'res.cloudinary.com')) {
            $parts = explode('/upload/', $url);
            if (count($parts) == 2) {
                $publicIdWithExt = explode('/', $parts[1]);
                array_shift($publicIdWithExt);
                $publicIdPath = implode('/', $publicIdWithExt);
                $publicId = pathinfo($publicIdPath, PATHINFO_DIRNAME) . '/' . pathinfo($publicIdPath, PATHINFO_FILENAME);
                try {
                    cloudinary()->uploadApi()->destroy($publicId);
                } catch (\Exception $e) {
                    Log::error("Failed to delete Cloudinary image: " . $e->getMessage());
                }
            }
        }
    }
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
            'logo' => 'nullable|image|max:3072',
            'background_image' => 'nullable|image|max:3072',
        ]);

        DB::beginTransaction();
        $uploadedCloudinaryIds = [];
        try {
            if ($request->hasFile('logo')) {
                $uploadResult = cloudinary()->uploadApi()->upload($request->file('logo')->getRealPath(), [
                    'folder' => 'hspace/linktree',
                    'format' => 'webp',
                    'transformation' => ['width' => 400, 'crop' => 'scale']
                ]);
                $uploadedCloudinaryIds[] = $uploadResult['public_id'];
                
                $this->deleteImage($settings->logo);
                $settings->logo = $uploadResult['secure_url'];
            }

            if ($request->hasFile('background_image')) {
                $uploadResult = cloudinary()->uploadApi()->upload($request->file('background_image')->getRealPath(), [
                    'folder' => 'hspace/linktree',
                    'format' => 'webp',
                    'transformation' => ['width' => 1200, 'crop' => 'scale']
                ]);
                $uploadedCloudinaryIds[] = $uploadResult['public_id'];

                $this->deleteImage($settings->background_image);
                $settings->background_image = $uploadResult['secure_url'];
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

            DB::commit();
            return response()->json(['message' => 'Settings updated successfully', 'data' => $settings]);
        } catch (\Exception $e) {
            DB::rollBack();
            foreach ($uploadedCloudinaryIds as $publicId) {
                try { cloudinary()->uploadApi()->destroy($publicId); } catch (\Exception $ex) {}
            }
            return response()->json(['message' => 'Failed to update settings: ' . $e->getMessage()], 500);
        }
    }

    public function storeLink(Request $request)
    {
        $request->validate([
            'icon' => 'required|string',
            'label' => 'required|string',
            'url' => 'required|string',
            'custom_icon' => 'nullable|image|max:3072',
        ]);

        DB::beginTransaction();
        try {
            $customIconUrl = null;
            if ($request->hasFile('custom_icon')) {
                $uploadResult = cloudinary()->uploadApi()->upload($request->file('custom_icon')->getRealPath(), [
                    'folder' => 'hspace/linktree',
                    'format' => 'webp',
                    'transformation' => ['width' => 100, 'crop' => 'scale']
                ]);
                $customIconUrl = $uploadResult['secure_url'];
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

            DB::commit();
            return response()->json(['message' => 'Link created successfully', 'data' => $link], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($uploadResult)) {
                try { cloudinary()->uploadApi()->destroy($uploadResult['public_id']); } catch (\Exception $ex) {}
            }
            return response()->json(['message' => 'Failed to create link: ' . $e->getMessage()], 500);
        }
    }

    public function updateLink(Request $request, $id)
    {
        $link = LinktreeLink::findOrFail($id);

        $request->validate([
            'icon' => 'required|string',
            'label' => 'required|string',
            'url' => 'required|string',
            'custom_icon' => 'nullable|image|max:3072',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('custom_icon')) {
                $uploadResult = cloudinary()->uploadApi()->upload($request->file('custom_icon')->getRealPath(), [
                    'folder' => 'hspace/linktree',
                    'format' => 'webp',
                    'transformation' => ['width' => 100, 'crop' => 'scale']
                ]);
                
                $this->deleteImage($link->custom_icon);
                $link->custom_icon = $uploadResult['secure_url'];
            } elseif ($request->has('remove_custom_icon') && $request->remove_custom_icon == 'true') {
                $this->deleteImage($link->custom_icon);
                $link->custom_icon = null;
            }

            $link->icon = $request->icon;
            $link->label = $request->label;
            $link->url = $request->url;
            $link->save();

            DB::commit();
            return response()->json(['message' => 'Link updated successfully', 'data' => $link]);
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($uploadResult)) {
                try { cloudinary()->uploadApi()->destroy($uploadResult['public_id']); } catch (\Exception $ex) {}
            }
            return response()->json(['message' => 'Failed to update link: ' . $e->getMessage()], 500);
        }
    }

    public function destroyLink($id)
    {
        $link = LinktreeLink::findOrFail($id);
        
        $this->deleteImage($link->custom_icon);

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
