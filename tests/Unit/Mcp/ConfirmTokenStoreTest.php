<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenIssuer;
use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenStore;
use Flexpik\FilamentStudio\Mcp\Exceptions\ConfirmTokenInvalidException;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    config(['filament-studio.mcp.confirm_token_ttl' => 300]);
    $this->store = new ConfirmTokenStore;
    $this->issuer = new ConfirmTokenIssuer($this->store);
});

it('issues a token with a ct_ prefix', function () {
    $result = $this->issuer->issue(operation: 'delete_collection', target: ['slug' => 'products'], tenantId: 1);

    expect($result['token'])->toStartWith('ct_')
        ->and($result['expires_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T/');
});

it('consumes a valid token exactly once', function () {
    $token = $this->issuer->issue('delete_collection', ['slug' => 'products'], 1)['token'];

    $payload = $this->store->consume($token, 'delete_collection', ['slug' => 'products'], 1);

    expect($payload['operation'])->toBe('delete_collection');
    expect(fn () => $this->store->consume($token, 'delete_collection', ['slug' => 'products'], 1))
        ->toThrow(ConfirmTokenInvalidException::class);
});

it('rejects a token whose payload does not match', function () {
    $token = $this->issuer->issue('delete_collection', ['slug' => 'products'], 1)['token'];

    expect(fn () => $this->store->consume($token, 'delete_collection', ['slug' => 'orders'], 1))
        ->toThrow(ConfirmTokenInvalidException::class);
});

it('rejects a token used by a different tenant', function () {
    $token = $this->issuer->issue('delete_collection', ['slug' => 'products'], 1)['token'];

    expect(fn () => $this->store->consume($token, 'delete_collection', ['slug' => 'products'], 2))
        ->toThrow(ConfirmTokenInvalidException::class);
});

it('rejects an expired token', function () {
    config(['filament-studio.mcp.confirm_token_ttl' => 1]);
    $token = (new ConfirmTokenIssuer(new ConfirmTokenStore))
        ->issue('delete_collection', ['slug' => 'products'], 1)['token'];

    sleep(2);

    expect(fn () => $this->store->consume($token, 'delete_collection', ['slug' => 'products'], 1))
        ->toThrow(ConfirmTokenInvalidException::class);
});
