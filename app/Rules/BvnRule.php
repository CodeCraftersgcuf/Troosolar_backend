<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BvnRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Trim and clean the BVN (remove all whitespace)
        $bvn = preg_replace('/\s+/', '', trim((string)$value));
        
        // Check length only (removed digits-only restriction as requested)
        if (strlen($bvn) !== 11) {
            $fail('The :attribute must be exactly 11 characters.');
            return;
        }
    }
}
