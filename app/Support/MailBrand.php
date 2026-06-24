<?php

namespace App\Support;

class MailBrand
{
    public const SLOGAN = 'Bridging the gap in affordable solar power solutions';

    /** Customer-facing product name — use consistently in emails and notifications. */
    public const BNPL_LABEL = 'Buy Now, Pay Later (BNPL)';

    /** Buy Now custom order label (admin-pushed cart, non-BNPL). */
    public const BUY_NOW_CUSTOM_ORDER_LABEL = 'Buy Now Custom Order';

    public static function logoUrl(): string
    {
        $override = config('app.mail_logo_url');
        if (is_string($override) && trim($override) !== '') {
            return trim($override);
        }

        return rtrim((string) config('app.url', 'https://api.troosolar.com'), '/').'/images/troosolar-logo.png';
    }
}
