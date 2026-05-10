<?php

declare(strict_types=1);

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

it('registers a studio-mcp rate limiter that buckets per X-Api-Key', function () {
    config()->set('filament-studio.mcp.enabled', true);
    config()->set('filament-studio.mcp.http.rate_limit', 120);

    $request = Request::create('/ai/studio', 'POST');
    $request->headers->set('X-Api-Key', 'sk_test_abc');

    $limit = RateLimiter::limiter('studio-mcp')($request);

    expect($limit)->toBeInstanceOf(Limit::class);
    expect($limit->maxAttempts)->toBe(120);
    expect($limit->key)->toBe('sk_test_abc');
});

it('falls back to the request IP when no X-Api-Key is sent', function () {
    config()->set('filament-studio.mcp.enabled', true);

    $request = Request::create('/ai/studio', 'POST');

    $limit = RateLimiter::limiter('studio-mcp')($request);

    expect($limit->key)->toBe($request->ip());
});
