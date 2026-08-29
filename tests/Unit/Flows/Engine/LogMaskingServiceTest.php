<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\LogMaskingService;

it('replaces sensitive top-level keys with ***', function () {
    $masker = new LogMaskingService;
    $masked = $masker->mask(
        ['to' => 'a@b.com', 'password' => 'secret'],
        sensitiveKeys: ['password'],
    );

    expect($masked)->toBe(['to' => 'a@b.com', 'password' => '***']);
});

it('walks nested arrays', function () {
    $masker = new LogMaskingService;
    $masked = $masker->mask(
        ['headers' => ['Authorization' => 'Bearer x', 'X-Trace' => 'ok']],
        sensitiveKeys: ['Authorization'],
    );

    expect($masked['headers']['Authorization'])->toBe('***');
    expect($masked['headers']['X-Trace'])->toBe('ok');
});

it('returns input unchanged when no sensitive keys', function () {
    $masker = new LogMaskingService;
    expect($masker->mask(['x' => 1], []))->toBe(['x' => 1]);
});
