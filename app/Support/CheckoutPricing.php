<?php

namespace App\Support;

use App\Models\CheckoutSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CheckoutPricing
{
    /**
     * Add N weekdays (Mon–Fri) starting from the given instant (day granularity).
     */
    public static function addWorkingDays(Carbon $from, int $workingDays): Carbon
    {
        if ($workingDays <= 0) {
            return $from->copy();
        }
        $d = $from->copy()->startOfDay();
        $added = 0;
        while ($added < $workingDays) {
            $d->addDay();
            if (! $d->isWeekend()) {
                $added++;
            }
        }

        return $d;
    }

    public static function installationTotalFromCartItems(Collection $cartItems): int
    {
        $sum = $cartItems->sum(function ($item) {
            if (! $item->itemable) {
                return 0;
            }
            $qty = max(1, (int) ($item->quantity ?? 1));
            $perUnit = (float) (
                $item->itemable->installation_price
                ?? $item->itemable->install_price
                ?? $item->itemable->installation_cost
                ?? 0
            );

            return max(0, $perUnit) * $qty;
        });

        return (int) round($sum);
    }

    public static function deliveryWindow(CheckoutSetting $settings): array
    {
        $base = Carbon::now()->startOfDay();
        $min = max(1, (int) $settings->delivery_min_working_days);
        $max = max($min, (int) $settings->delivery_max_working_days);
        $from = self::addWorkingDays($base, $min);
        $to = self::addWorkingDays($base, $max);
        $label = "{$min}–{$max} working days";

        return [
            'estimated_from' => $from->toDateString(),
            'estimated_to' => $to->toDateString(),
            'label' => $label,
            'min_working_days' => $min,
            'max_working_days' => $max,
        ];
    }

    public static function installationEstimatedDate(CheckoutSetting $settings): string
    {
        $days = max(1, (int) $settings->installation_schedule_working_days);
        $base = Carbon::now()->startOfDay();

        return self::addWorkingDays($base, $days)->toDateString();
    }

    /** Insurance as % of (items subtotal + full installation amount). */
    public static function insuranceAmountFromPercent(float $itemsSubtotal, float $installationFull, float $percent): int
    {
        if ($percent <= 0) {
            return 0;
        }
        $base = max(0, $itemsSubtotal) + max(0, $installationFull);

        return (int) round($base * ($percent / 100.0));
    }

    public static function vatAmount(float $taxableBase, float $vatPercent): int
    {
        if ($vatPercent <= 0 || $taxableBase <= 0) {
            return 0;
        }

        return (int) round($taxableBase * ($vatPercent / 100.0));
    }
}
