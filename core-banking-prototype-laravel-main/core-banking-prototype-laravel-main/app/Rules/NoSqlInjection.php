<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoSqlInjection implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        // SQL injection patterns to detect
        $patterns = [
            '/SELECT.*FROM/i',
            '/UNION.*SELECT/i',
            '/INSERT.*INTO/i',
            '/UPDATE.*SET/i',
            '/DELETE.*FROM/i',
            '/DROP.*TABLE/i',
            '/CREATE.*TABLE/i',
            '/ALTER.*TABLE/i',
            '/\-\-/', // SQL comments
            '/\/\*.*\*\//', // SQL block comments
            "/'\s*OR\s*'?\d*'\s*=\s*'?\d*/i", // Common OR injection pattern
            "/'\s*;\s*(DROP|DELETE|INSERT|UPDATE)/i", // Command injection
            '/\x00/', // Null bytes
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $fail('The :attribute field contains potentially dangerous SQL patterns.');

                return;
            }
        }
    }
}
