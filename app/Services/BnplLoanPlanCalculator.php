<?php

namespace App\Services;

/**
 * Mirrors customer BNPL "Review Your Loan Plan" math (BNPLFlow buildLoanReviewSnapshot):
 * bundle price, % deposit on bundle, admin fees (insurance on bundle; mgmt/legal on loan amount), interest = monthly% × tenor on principal.
 */
class BnplLoanPlanCalculator
{
    public static function toFloat(mixed $v): float
    {
        if ($v === null || $v === '') {
            return 0.0;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }
        $s = preg_replace('/,/', '', (string) $v);

        return is_numeric($s) ? (float) $s : 0.0;
    }

    /**
     * LoanCalculator passes `totalAmount` = cart grand total (items + install/delivery/inspection + VAT).
     * That equals principal + equity deposit. Prefer that sum from snapshot — it matches BNPLFlow + LoanCalculator.jsx.
     *
     * @param  array<string, mixed>|null  $snap  loan_plan_snapshot (formData.loanDetails JSON)
     */
    public static function bundlePriceFromSnapshot(?array $snap): float
    {
        if (! is_array($snap) || $snap === []) {
            return 0.0;
        }
        $principal = self::toFloat($snap['principal'] ?? $snap['totalLoanAmount'] ?? 0);
        $baseDep = self::toFloat($snap['baseDepositAmount'] ?? 0);
        if ($principal > 0 && $baseDep >= 0) {
            return max($principal + $baseDep, 0.0);
        }
        $totalAmount = self::toFloat($snap['totalAmount'] ?? 0);
        $adminFees = self::toFloat($snap['adminFeesTotal'] ?? 0);
        if ($adminFees <= 0) {
            $adminFees = self::toFloat($snap['insuranceFee'] ?? 0)
                + self::toFloat($snap['managementFee'] ?? 0)
                + self::toFloat($snap['legalFee'] ?? 0);
        }
        if ($totalAmount > 0) {
            return max($totalAmount - $adminFees, 0.0);
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>|null  $snap
     * @return array{insurance: float, management: float, legal: float}
     */
    public static function feePercentagesFromSnapshot(?array $snap): array
    {
        $fp = is_array($snap) && isset($snap['feePercentages']) && is_array($snap['feePercentages'])
            ? $snap['feePercentages']
            : null;
        if ($fp !== null) {
            return [
                'insurance' => self::toFloat($fp['insurance'] ?? 3),
                'management' => self::toFloat($fp['management'] ?? 1),
                'legal' => self::toFloat($fp['legal'] ?? 1),
            ];
        }

        return [
            'insurance' => self::toFloat($snap['insurancePct'] ?? $snap['insurance_fee_percentage'] ?? 3),
            'management' => self::toFloat($snap['managementPct'] ?? $snap['management_fee_percentage'] ?? 1),
            'legal' => self::toFloat($snap['legalPct'] ?? $snap['legal_fee_percentage'] ?? 1),
        ];
    }

    public static function interestMonthlyPercentFromSnapshot(?array $snap, float $fallback = 4.0): float
    {
        if (! is_array($snap)) {
            return $fallback;
        }
        foreach (['interestRate', 'interest_rate'] as $k) {
            if (isset($snap[$k]) && $snap[$k] !== '' && is_numeric($snap[$k])) {
                return (float) $snap[$k];
            }
        }

        return $fallback;
    }

    /**
     * @param  array{insurance: float, management: float, legal: float}  $feePcts
     * @return array<string, float>
     */
    public static function computeFromDepositPercent(
        float $bundlePrice,
        float $depositPercentOfBundle,
        int $tenorMonths,
        float $interestMonthlyPercent,
        array $feePcts
    ): array {
        $i = $feePcts['insurance'] / 100;
        $m = $feePcts['management'] / 100;
        $l = $feePcts['legal'] / 100;

        // Same as LoanCalculator.jsx: depositAmount = (totalAmount * depositPercent) / 100; principal = totalAmount - depositAmount
        $baseDeposit = $bundlePrice * ($depositPercentOfBundle / 100.0);
        $baseLoanAmount = max($bundlePrice - $baseDeposit, 0.0);

        $insuranceFee = round($bundlePrice * $i, 2);
        $managementFee = round($baseLoanAmount * $m, 2);
        $legalFee = round($baseLoanAmount * $l, 2);
        $feesTotal = round($insuranceFee + $managementFee + $legalFee, 2);
        $upfrontDepositTotal = round($baseDeposit + $feesTotal, 2);

        $totalInterestAmount = round($baseLoanAmount * ($interestMonthlyPercent / 100) * max($tenorMonths, 0), 2);
        $totalRepaymentAmount = round($baseLoanAmount + $totalInterestAmount, 2);
        $monthlyRepaymentAmount = $tenorMonths > 0 ? round($totalRepaymentAmount / $tenorMonths, 2) : 0.0;

        return [
            'bundle_price' => round($bundlePrice, 2),
            'deposit_percent' => $depositPercentOfBundle,
            'base_deposit' => $baseDeposit,
            'base_loan_amount' => $baseLoanAmount,
            'insurance_fee' => $insuranceFee,
            'management_fee' => $managementFee,
            'legal_fee' => $legalFee,
            'admin_fees_total' => $feesTotal,
            'upfront_deposit_total' => $upfrontDepositTotal,
            'total_loan_amount' => $baseLoanAmount,
            'total_interest_amount' => $totalInterestAmount,
            'total_repayment_amount' => $totalRepaymentAmount,
            'monthly_repayment_amount' => $monthlyRepaymentAmount,
        ];
    }

    /**
     * Recover equity deposit (before fees) from total upfront paid (deposit + admin fees).
     *
     * @param  array{insurance: float, management: float, legal: float}  $feePcts
     */
    public static function solveBaseDepositFromUpfront(float $bundlePrice, float $upfrontTotal, array $feePcts): float
    {
        if ($bundlePrice <= 0 || $upfrontTotal <= 0) {
            return 0.0;
        }
        $i = $feePcts['insurance'] / 100;
        $m = $feePcts['management'] / 100;
        $l = $feePcts['legal'] / 100;
        $denom = 1 - $m - $l;
        if ($denom <= 0.0001) {
            return 0.0;
        }
        $base = ($upfrontTotal - $bundlePrice * ($i + $m + $l)) / $denom;

        return max(0.0, round($base, 2));
    }

    /**
     * Build full plan from stored counter-offer upfront total (matches mono.down_payment semantics on submit).
     *
     * @param  array{insurance: float, management: float, legal: float}  $feePcts
     * @return array<string, float>
     */
    public static function computeFromUpfrontTotal(
        float $bundlePrice,
        float $upfrontTotal,
        int $tenorMonths,
        float $interestMonthlyPercent,
        array $feePcts
    ): array {
        $baseDeposit = self::solveBaseDepositFromUpfront($bundlePrice, $upfrontTotal, $feePcts);
        $depositPercent = $bundlePrice > 0 ? ($baseDeposit / $bundlePrice) * 100 : 0.0;

        return self::computeFromDepositPercent(
            $bundlePrice,
            $depositPercent,
            $tenorMonths,
            $interestMonthlyPercent,
            $feePcts
        );
    }
}
