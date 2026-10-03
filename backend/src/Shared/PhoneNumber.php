<?php

declare(strict_types=1);

namespace App\Shared;

final class PhoneNumber
{
    public static function normalize(string $value): ?string
    {
        $digits = preg_replace('/[+\s()-]/', '', $value);
        return is_string($digits) && preg_match('/^\d{10,15}$/D', $digits) ? $digits : null;
    }
}
