<?php

namespace Flexpik\FilamentStudio\Filtering;

use Filament\Facades\Filament;

class DynamicValueResolver
{
    /**
     * Resolve a value, substituting dynamic variable tokens.
     */
    public static function resolve(mixed $value): mixed
    {
        if (! is_string($value) || ! static::isDynamic($value)) {
            return $value;
        }

        if ($value === '$CURRENT_USER') {
            return auth()->id();
        }

        if ($value === '$CURRENT_TENANT') {
            return Filament::getTenant()?->getKey();
        }

        if (str_starts_with($value, '$NOW')) {
            return static::resolveNow($value);
        }

        return $value;
    }

    public static function isDynamic(mixed $value): bool
    {
        return is_string($value) && str_starts_with($value, '$');
    }

    protected static function resolveNow(string $token): string
    {
        $now = now();

        if (preg_match('/^\$NOW\((.+)\)$/', $token, $matches)) {
            $now = $now->modify($matches[1]);
        }

        return $now->format('Y-m-d H:i:s');
    }
}
