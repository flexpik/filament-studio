<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Engine;

class LogMaskingService
{
    /**
     * @param  array<int|string, mixed>  $value
     * @param  array<int, string>  $sensitiveKeys
     * @return array<int|string, mixed>
     */
    public function mask(array $value, array $sensitiveKeys): array
    {
        if ($sensitiveKeys === []) {
            return $value;
        }

        $set = array_flip($sensitiveKeys);

        $walk = function (mixed $node) use (&$walk, $set): mixed {
            if (! is_array($node)) {
                return $node;
            }

            $out = [];
            foreach ($node as $k => $v) {
                $out[$k] = isset($set[$k]) ? '***' : $walk($v);
            }

            return $out;
        };

        /** @var array<int|string, mixed> $masked */
        $masked = $walk($value);

        return $masked;
    }
}
