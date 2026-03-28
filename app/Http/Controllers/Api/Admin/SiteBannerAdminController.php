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
     * Home + sidebar banners (admin).
     * GET /api/admin/site/banner
     */
    public function show(Request $request)
    {
        $home = SiteBanner::where('key', SiteBanner::KEY_HOME_PROMO)->first();
        $sidebar = SiteBanner::where('key', SiteBanner::KEY_SIDEBAR_PROMO)->first();
        $homePayload = SiteBanner::apiPayload($request, $home);

        return ResponseHelper::success([
            'home' => $homePayload,
            'sidebar' => SiteBanner::apiPayload($request, $sidebar),
            'url' => $homePayload['url'],
            'path' => $homePayload['path'],
        ], 'Banners retrieved');
    }

    /**
     * Upload or replace a promotion banner.
     * POST /api/admin/site/banner  (multipart: banner, placement=home|sidebar)
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'banner' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
                'placement' => 'nullable|in:home,sidebar',
            ]);
            $file = $request->file('banner');
            $dir = 'banners';
            $isSidebar = $request->input('placement') === 'sidebar';
            $prefix = $isSidebar ? 'sidebar_promotion_' : 'home_promotion_';
            $name = $prefix . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path($dir), $name);
            $path = $dir . '/' . $name;

            $key = $isSidebar ? SiteBanner::KEY_SIDEBAR_PROMO : SiteBanner::KEY_HOME_PROMO;
            $banner = SiteBanner::firstOrNew(['key' => $key]);
            $oldPath = $banner->path;
            $banner->path = $path;
            $banner->save();

            if ($oldPath && $oldPath !== $path && is_file(public_path($oldPath))) {
                @unlink(public_path($oldPath));
            }

            $payload = SiteBanner::apiPayload($request, $banner);

            return ResponseHelper::success([
                'placement' => $isSidebar ? 'sidebar' : 'home',
                'url' => $payload['url'],
                'path' => $payload['path'],
            ], 'Banner updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Site banner upload error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to upload banner', 500);
        }
    }

    /**
     * Remove a promotion banner.
     * DELETE /api/admin/site/banner?placement=home|sidebar
     */
    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'placement' => 'nullable|in:home,sidebar',
            ]);
            $raw = $request->query('placement') ?? $request->input('placement');
            $placement = $raw === 'sidebar' ? 'sidebar' : 'home';
            $key = $placement === 'sidebar'
                ? SiteBanner::KEY_SIDEBAR_PROMO
                : SiteBanner::KEY_HOME_PROMO;
            $banner = SiteBanner::where('key', $key)->first();
            if ($banner && $banner->path && is_file(public_path($banner->path))) {
                @unlink(public_path($banner->path));
            }
            if ($banner) {
                $banner->path = null;
                $banner->save();
            }

            return ResponseHelper::success(null, 'Banner removed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Site banner delete error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to remove banner', 500);
        }
    }
}
