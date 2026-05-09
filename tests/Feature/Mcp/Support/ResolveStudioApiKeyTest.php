<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Support\ResolveStudioApiKey;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

it('rejects requests without an X-Api-Key header', function () {
    $middleware = app(ResolveStudioApiKey::class);
    $request = Request::create('/ai/studio', 'POST');

    $response = $middleware->handle($request, fn () => new Response('ok'));

    $body = json_decode($response->getContent(), true);

    expect($response->getStatusCode())->toBe(401);
    expect($body['error']['code'])->toBe('STUDIO_UNAUTHENTICATED');
});

it('rejects requests with an unknown X-Api-Key', function () {
    $middleware = app(ResolveStudioApiKey::class);
    $request = Request::create('/ai/studio', 'POST');
    $request->headers->set('X-Api-Key', 'sk_unknown');

    $response = $middleware->handle($request, fn () => new Response('ok'));

    expect($response->getStatusCode())->toBe(401);
});

it('rejects an inactive API key', function () {
    StudioApiKey::factory()->create(['key' => 'sk_inactive', 'is_active' => false]);

    $middleware = app(ResolveStudioApiKey::class);
    $request = Request::create('/ai/studio', 'POST');
    $request->headers->set('X-Api-Key', 'sk_inactive');

    $response = $middleware->handle($request, fn () => new Response('ok'));

    expect($response->getStatusCode())->toBe(401);
});

it('rejects an expired API key', function () {
    StudioApiKey::factory()->create([
        'key' => 'sk_expired',
        'is_active' => true,
        'expires_at' => now()->subDay(),
    ]);

    $middleware = app(ResolveStudioApiKey::class);
    $request = Request::create('/ai/studio', 'POST');
    $request->headers->set('X-Api-Key', 'sk_expired');

    $response = $middleware->handle($request, fn () => new Response('ok'));

    expect($response->getStatusCode())->toBe(401);
});

it('binds a valid key to the StudioApiKeyContext and updates last_used_at', function () {
    $key = StudioApiKey::factory()->create([
        'key' => 'sk_valid',
        'is_active' => true,
        'last_used_at' => null,
    ]);

    $middleware = app(ResolveStudioApiKey::class);
    $request = Request::create('/ai/studio', 'POST');
    $request->headers->set('X-Api-Key', 'sk_valid');

    $middleware->handle($request, fn () => new Response('ok'));

    expect(app(StudioApiKeyContext::class)->current()?->id)->toBe($key->id);
    expect($key->fresh()->last_used_at)->not->toBeNull();
});
