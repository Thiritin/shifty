<?php

namespace App\Support;

class ClaimMatcher
{
    /**
     * Arrays match on any element, strings are split on commas and whitespace. Comparison is exact per element.
     */
    public static function matches(array $claims, string $path, string $value): bool
    {
        $claim = data_get($claims, $path);

        $candidates = match (true) {
            is_array($claim) => $claim,
            is_string($claim), is_int($claim) => preg_split('/[\s,]+/', (string) $claim, -1, PREG_SPLIT_NO_EMPTY),
            default => [],
        };

        foreach ($candidates as $candidate) {
            if (is_scalar($candidate) && (string) $candidate === $value) {
                return true;
            }
        }

        return false;
    }
}
