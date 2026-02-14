<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\SiteBanner;
use Illuminate\Http\Request;

class SiteBannerController extends Controller
{
    /**
     * Get current home promotion banner (public, no auth).
     * GET /api/site/banner
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
}
