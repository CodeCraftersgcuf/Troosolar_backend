<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\SiteBanner;
use Illuminate\Http\Request;

class SiteBannerController extends Controller
{
    /**
     * Home + sidebar promotion banners (public, no auth).
     * GET /api/site/banner
     *
     * Legacy fields `url` and `path` mirror the home banner for older clients.
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
}
