<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Security;

class MasksSensitiveValues
{
    /**
     * Recursively walk $payload and replace values whose keys match any of the
     * configured regex patterns with '***'.
     *
     * @param  array<mixed>  $payload
     * @return array<mixed>
     */
    public function mask(array $payload): array
    {
        $patterns = config('filament-studio.flows.sensitive_key_patterns', [
            '/password/i',
            '/token/i',
            '/secret/i',
            '/api[_-]?key/i',
            '/authorization/i',
            '/bearer/i',
        ]);

        return $this->walkAndMask($payload, $patterns);
    }

    /**
     * @param  array<mixed>  $payload
     * @param  array<string>  $patterns
     * @return array<mixed>
     */
    private function walkAndMask(array $payload, array $patterns): array
    {
        foreach ($payload as $key => $value) {
            $keyStr = (string) $key;
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $keyStr)) {
                    $payload[$key] = '***';
                    break; // key matched, no need to recurse into it
                }
            }
            // If not masked and value is array, recurse
            if ($payload[$key] !== '***' && is_array($value)) {
                $payload[$key] = $this->walkAndMask($value, $patterns);
            }
        }

        return $payload;
    }
}
