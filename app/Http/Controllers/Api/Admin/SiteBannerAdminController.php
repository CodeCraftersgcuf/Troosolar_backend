<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\SiteBanner;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SiteBannerAdminController extends Controller
{
    /**
     * Get current banner (admin).
     * GET /api/admin/site/banner
     */
    public function show()
    {
        $banner = SiteBanner::where('key', SiteBanner::KEY_HOME_PROMO)->first();
        $url = null;
        if ($banner && !empty($banner->path)) {
            $url = $banner->path;
            if (!str_starts_with($url, 'http')) {
                $url = rtrim(config('app.url'), '/') . '/' . ltrim($url, '/');
            }
        }
        return ResponseHelper::success([
            'url' => $url,
            'path' => $banner->path ?? null,
        ], 'Banner retrieved');
    }

    /**
     * Upload or replace home promotion banner.
     * POST /api/admin/site/banner
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'banner' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
            ]);
            $file = $request->file('banner');
            $dir = 'banners';
            $name = 'home_promotion_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path($dir), $name);
            $path = $dir . '/' . $name;

            $banner = SiteBanner::firstOrNew(['key' => SiteBanner::KEY_HOME_PROMO]);
            $oldPath = $banner->path;
            $banner->path = $path;
            $banner->save();

            if ($oldPath && $oldPath !== $path && is_file(public_path($oldPath))) {
                @unlink(public_path($oldPath));
            }

            $url = rtrim(config('app.url'), '/') . '/' . ltrim($path, '/');
            return ResponseHelper::success([
                'url' => $url,
                'path' => $path,
            ], 'Banner updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Site banner upload error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to upload banner', 500);
        }
    }

    /**
     * Remove home promotion banner.
     * DELETE /api/admin/site/banner
     */
    public function destroy()
    {
        try {
            $banner = SiteBanner::where('key', SiteBanner::KEY_HOME_PROMO)->first();
            if ($banner && $banner->path && is_file(public_path($banner->path))) {
                @unlink(public_path($banner->path));
            }
            if ($banner) {
                $banner->path = null;
                $banner->save();
            }
            return ResponseHelper::success(null, 'Banner removed');
        } catch (Exception $e) {
            Log::error('Site banner delete error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to remove banner', 500);
        }
    }
}
