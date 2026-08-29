<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Security;

use Flexpik\FilamentStudio\Flows\Security\Exceptions\InvalidWebhookSignatureException;
use Flexpik\FilamentStudio\Flows\Security\Exceptions\StaleWebhookTimestampException;

class HmacWebhookVerifier
{
    /**
     * Verify a webhook request's HMAC-SHA256 signature with timestamp window enforcement.
     *
     * Signed string format: "{timestamp}.{rawBody}"
     *
     * @throws InvalidWebhookSignatureException if sig or timestamp is empty, or signature does not match
     * @throws StaleWebhookTimestampException if the timestamp is outside the configured window
     */
    public function verify(string $body, string $sig, string $timestamp, string $secret): bool
    {
        if ($sig === '' || $timestamp === '') {
            throw new InvalidWebhookSignatureException('Missing webhook signature or timestamp.');
        }

        $window = (int) config('filament-studio.flows.webhook_timestamp_window_seconds', 300);

        if (abs(time() - (int) $timestamp) > $window) {
            throw new StaleWebhookTimestampException('Webhook timestamp is outside the acceptable window.');
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

        if (! hash_equals($expected, $sig)) {
            throw new InvalidWebhookSignatureException('Webhook signature does not match.');
        }

        return true;
    }
}
