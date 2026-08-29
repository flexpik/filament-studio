<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Security;

/**
 * @deprecated Use {@see HmacWebhookVerifier} for strict signature + timestamp window verification.
 */
class HmacVerifier
{
    public function verify(string $rawBody, string $headerValue, string $secret): bool
    {
        if ($secret === '') {
            return false;
        }
        if (! str_starts_with($headerValue, 'sha256=')) {
            return false;
        }

        $provided = substr($headerValue, 7);
        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $provided);
    }
}
