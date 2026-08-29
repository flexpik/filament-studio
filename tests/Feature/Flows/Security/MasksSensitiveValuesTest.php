<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Security\MasksSensitiveValues;

it('masks password keys', function () {
    $masker = app(MasksSensitiveValues::class);
    $result = $masker->mask(['username' => 'alice', 'password' => 'secret']);
    expect($result['password'])->toBe('***');
    expect($result['username'])->toBe('alice');
});

it('masks nested api_key', function () {
    $masker = app(MasksSensitiveValues::class);
    $result = $masker->mask(['data' => ['api_key' => 'abc123', 'value' => 'ok']]);
    expect($result['data']['api_key'])->toBe('***');
    expect($result['data']['value'])->toBe('ok');
});

it('masks token and secret', function () {
    $masker = app(MasksSensitiveValues::class);
    $result = $masker->mask(['auth_token' => 'tok', 'webhook_secret' => 'shhh', 'name' => 'test']);
    expect($result['auth_token'])->toBe('***');
    expect($result['webhook_secret'])->toBe('***');
    expect($result['name'])->toBe('test');
});

it('leaves non-sensitive keys untouched', function () {
    $masker = app(MasksSensitiveValues::class);
    $result = $masker->mask(['url' => 'https://example.com', 'method' => 'GET', 'timeout' => 30]);
    expect($result)->toBe(['url' => 'https://example.com', 'method' => 'GET', 'timeout' => 30]);
});
