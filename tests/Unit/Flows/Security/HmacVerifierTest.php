<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Security\HmacVerifier;

it('verifies a correct sha256 signature', function () {
    $body = '{"hello":"world"}';
    $secret = 'top-secret';
    $sig = 'sha256='.hash_hmac('sha256', $body, $secret);

    expect((new HmacVerifier)->verify($body, $sig, $secret))->toBeTrue();
});

it('rejects an altered body', function () {
    $secret = 'top-secret';
    $sig = 'sha256='.hash_hmac('sha256', 'original', $secret);

    expect((new HmacVerifier)->verify('tampered', $sig, $secret))->toBeFalse();
});

it('rejects a malformed header', function () {
    expect((new HmacVerifier)->verify('x', 'not-a-real-header', 'k'))->toBeFalse();
});

it('rejects when secret is empty', function () {
    expect((new HmacVerifier)->verify('x', 'sha256=abc', ''))->toBeFalse();
});
