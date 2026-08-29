<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Security\Exceptions\InvalidWebhookSignatureException;
use Flexpik\FilamentStudio\Flows\Security\Exceptions\StaleWebhookTimestampException;
use Flexpik\FilamentStudio\Flows\Security\HmacWebhookVerifier;

beforeEach(fn () => config()->set('filament-studio.flows.webhook_timestamp_window_seconds', 300));

it('accepts a valid signature within the window', function () {
    $body = '{"foo":"bar"}';
    $secret = 'shhh';
    $ts = (string) time();
    $sig = hash_hmac('sha256', $ts.'.'.$body, $secret);

    $verifier = app(HmacWebhookVerifier::class);
    expect($verifier->verify($body, $sig, $ts, $secret))->toBeTrue();
});

it('rejects an invalid signature', function () {
    $body = '{"foo":"bar"}';
    $secret = 'shhh';
    $ts = (string) time();
    $sig = 'invalid_signature_here';

    $verifier = app(HmacWebhookVerifier::class);
    expect(fn () => $verifier->verify($body, $sig, $ts, $secret))
        ->toThrow(InvalidWebhookSignatureException::class);
});

it('rejects a stale timestamp', function () {
    $body = '{}';
    $secret = 'shhh';
    $ts = (string) (time() - 1000);
    $sig = hash_hmac('sha256', $ts.'.'.$body, $secret);

    $verifier = app(HmacWebhookVerifier::class);
    expect(fn () => $verifier->verify($body, $sig, $ts, $secret))
        ->toThrow(StaleWebhookTimestampException::class);
});

it('rejects empty signature', function () {
    $verifier = app(HmacWebhookVerifier::class);
    expect(fn () => $verifier->verify('body', '', (string) time(), 'secret'))
        ->toThrow(InvalidWebhookSignatureException::class);
});

it('rejects empty timestamp', function () {
    $verifier = app(HmacWebhookVerifier::class);
    expect(fn () => $verifier->verify('body', 'sig', '', 'secret'))
        ->toThrow(InvalidWebhookSignatureException::class);
});
