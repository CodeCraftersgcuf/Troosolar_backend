<?php

namespace App\Support;

class MailBrand
{
    public const SLOGAN = 'Bridging the gap in affordable solar power solutions';

    /** Customer-facing product name — use consistently in emails and notifications. */
    public const BNPL_LABEL = 'Buy Now, Pay Later (BNPL)';

    /** Buy Now custom order label (admin-pushed cart, non-BNPL). */
    public const BUY_NOW_CUSTOM_ORDER_LABEL = 'Buy Now Custom Order';

    /**
     * Title-case for email headings — capitalizes each word consistently.
     * Preserves brand phrases (BNPL label, OTP, etc.).
     */
    public static function heading(string $text): string
    {
        $protected = [
            self::BNPL_LABEL,
            self::BUY_NOW_CUSTOM_ORDER_LABEL,
            'OTP',
            'BNPL',
            'Troosolar',
        ];

        usort($protected, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        $placeholders = [];
        foreach ($protected as $i => $literal) {
            if ($literal === '' || ! str_contains($text, $literal)) {
                continue;
            }
            $token = '{'.($i + 1000).'}';
            $placeholders[$token] = $literal;
            $text = str_replace($literal, $token, $text);
        }

        $parts = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
        foreach ($parts as $index => $part) {
            if (trim($part) === '' || isset($placeholders[$part])) {
                continue;
            }
            $parts[$index] = mb_convert_case($part, MB_CASE_TITLE, 'UTF-8');
        }

        $text = implode('', $parts);

        return str_replace(array_keys($placeholders), array_values($placeholders), $text);
    }

    public static function logoUrl(): string
    {
        $override = config('app.mail_logo_url');
        if (is_string($override) && trim($override) !== '') {
            return trim($override);
        }

        return rtrim((string) config('app.url', 'https://api.troosolar.com'), '/').'/images/troosolar-logo.png';
    }
}
