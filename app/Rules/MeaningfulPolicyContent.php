<?php

namespace App\Rules;

use App\Support\PolicyContentSanitizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class MeaningfulPolicyContent implements ValidationRule
{
    public function __construct(private PolicyContentSanitizer $sanitizer) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! $this->sanitizer->hasMeaningfulContent($value)) {
            $fail('The policy content must contain readable text.');
        }
    }
}
