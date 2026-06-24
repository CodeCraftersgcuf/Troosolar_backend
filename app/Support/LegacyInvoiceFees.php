<?php

namespace App\Support;

/**
 * Placeholder fee amounts seeded in migrations / old code paths.
 * These must not be charged unless admin replaces them with real values.
 */
class LegacyInvoiceFees
{
    public const LEGACY_DELIVERY = 25000.0;

    public const LEGACY_INSTALLATION = 50000.0;

    public const LEGACY_INSPECTION = 10000.0;

    public static function isLegacyDelivery(float $amount): bool
    {
        return $amount > 0 && abs($amount - self::LEGACY_DELIVERY) < 0.01;
    }

    public static function isLegacyInstallation(float $amount): bool
    {
        return $amount > 0 && abs($amount - self::LEGACY_INSTALLATION) < 0.01;
    }

    public static function isLegacyInspection(float $amount): bool
    {
        return $amount > 0 && abs($amount - self::LEGACY_INSPECTION) < 0.01;
    }

    /**
     * Return 0 for known legacy placeholder amounts; otherwise the configured amount.
     */
    public static function effectiveAmount(float $amount, string $kind): float
    {
        if ($amount <= 0) {
            return 0.0;
        }

        return match ($kind) {
            'delivery' => self::isLegacyDelivery($amount) ? 0.0 : $amount,
            'installation' => self::isLegacyInstallation($amount) ? 0.0 : $amount,
            'inspection' => self::isLegacyInspection($amount) ? 0.0 : $amount,
            default => $amount,
        };
    }
}
