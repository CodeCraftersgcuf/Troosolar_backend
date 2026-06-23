<?php

namespace App\Support;

class MailBrand
{
    public const SLOGAN = 'Bridging the gap in affordable solar power solutions';

    public static function logoUrl(): string
    {
        $override = config('app.mail_logo_url');
        if (is_string($override) && trim($override) !== '') {
            return trim($override);
        }

        return rtrim((string) config('app.url', 'https://api.troosolar.com'), '/').'/images/troosolar-logo.png';
    }
}
