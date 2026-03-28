<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SiteBanner extends Model
{
    protected $fillable = ['key', 'path'];

    public const KEY_HOME_PROMO = 'home_promotion';

    public const KEY_SIDEBAR_PROMO = 'dashboard_sidebar';

    /** Absolute URL for a stored public path (e.g. banners/foo.png), using request host when available. */
    public static function resolvePublicUrl(?Request $request, ?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        $base = $request
            ? rtrim($request->getSchemeAndHttpHost(), '/')
            : rtrim((string) config('app.url'), '/');

        return $base . '/' . ltrim($path, '/');
    }

    /** { url, path } for API responses. */
    public static function apiPayload(?Request $request, ?self $row): array
    {
        if (!$row || empty($row->path)) {
            return ['url' => null, 'path' => null];
        }

        return [
            'path' => $row->path,
            'url' => self::resolvePublicUrl($request, $row->path),
        ];
    }
}
