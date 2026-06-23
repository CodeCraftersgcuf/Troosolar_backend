<?php

namespace App\Support;

class FrontendUrl
{
    public static function base(): string
    {
        return rtrim((string) config('app.frontend_url', 'https://app.troosolar.io'), '/');
    }

    /**
     * Login URL that returns the user to an internal path after auth (email-safe deep link).
     */
    public static function loginWithReturn(string $returnPath): string
    {
        $returnPath = trim($returnPath);
        if ($returnPath === '' || ! str_starts_with($returnPath, '/') || str_starts_with($returnPath, '//')) {
            return self::base().'/login';
        }

        return self::base().'/login?return='.rawurlencode($returnPath);
    }

    public static function bnplApplicationTrack(int $applicationId): string
    {
        return self::loginWithReturn('/bnpl-loans/app-'.$applicationId);
    }

    public static function cartAccess(string $accessToken, string $orderType): string
    {
        $tokenEnc = rawurlencode($accessToken);
        $typeEnc = rawurlencode($orderType);

        return self::base()."/cart?token={$tokenEnc}&type={$typeEnc}";
    }
}
