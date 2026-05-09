<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Models\StudioApiKey;

beforeEach(function () {
    app()->forgetInstance(StudioApiKeyContext::class);
});

it('returns null when no key has been set', function () {
    $ctx = app(StudioApiKeyContext::class);

    expect($ctx->current())->toBeNull();
});

it('throws when require() is called without a key set', function () {
    $ctx = app(StudioApiKeyContext::class);

    expect(fn () => $ctx->require())
        ->toThrow(RuntimeException::class, 'Studio API key is not bound');
});

it('stores and returns a bound key', function () {
    $key = StudioApiKey::factory()->make(['id' => 42, 'name' => 'Test']);

    $ctx = app(StudioApiKeyContext::class);
    $ctx->set($key);

    expect($ctx->current())->toBe($key);
    expect($ctx->require())->toBe($key);
});

it('clears a bound key', function () {
    $key = StudioApiKey::factory()->make();

    $ctx = app(StudioApiKeyContext::class);
    $ctx->set($key);
    $ctx->clear();

    expect($ctx->current())->toBeNull();
});
