<?php

use Flexpik\FilamentStudio\Api\Middleware\ValidateApiKey;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create(['slug' => 'posts']);
});

/** @param array<string, string> $parameters */
function makeRequest(string $uri, string $method = 'GET', array $parameters = []): Request
{
    $request = Request::create($uri, $method);

    /** @var Router $router */
    $router = app('router');
    $route = $router->get('/api/studio/{collection_slug}', fn () => 'ok');
    if ($method === 'POST') {
        $route = $router->post('/api/studio/{collection_slug}', fn () => 'ok');
    }

    $router->getRoutes()->add($route);
    $route->bind($request);

    foreach ($parameters as $key => $value) {
        $route->setParameter($key, $value);
    }

    $request->setRouteResolver(fn () => $route);

    return $request;
}

it('rejects request without API key header', function () {
    $request = makeRequest('/api/studio/posts');

    $middleware = new ValidateApiKey;
    $response = $middleware->handle($request, fn () => new Response('ok'), 'index');

    expect($response->getStatusCode())->toBe(401);
});

it('rejects request with invalid API key', function () {
    $request = makeRequest('/api/studio/posts');
    $request->headers->set('X-Api-Key', 'invalid-key');

    $middleware = new ValidateApiKey;
    $response = $middleware->handle($request, fn () => new Response('ok'), 'index');

    expect($response->getStatusCode())->toBe(401);
});

it('rejects request when key lacks permission for action', function () {
    $plain = Str::random(64);
    StudioApiKey::factory()->create([
        'key' => hash('sha256', $plain),
        'permissions' => ['posts' => ['index']],
    ]);

    $request = makeRequest('/api/studio/posts', 'POST', ['collection_slug' => 'posts']);
    $request->headers->set('X-Api-Key', $plain);

    $middleware = new ValidateApiKey;
    $response = $middleware->handle($request, fn () => new Response('ok'), 'store');

    expect($response->getStatusCode())->toBe(403);
});

it('allows request with valid key and permission', function () {
    $plain = Str::random(64);
    StudioApiKey::factory()->create([
        'key' => hash('sha256', $plain),
        'permissions' => ['posts' => ['index', 'show']],
    ]);

    $request = makeRequest('/api/studio/posts', 'GET', ['collection_slug' => 'posts']);
    $request->headers->set('X-Api-Key', $plain);

    $middleware = new ValidateApiKey;
    $response = $middleware->handle($request, fn () => new Response('ok'), 'index');

    expect($response->getStatusCode())->toBe(200);
});

it('updates last_used_at on successful auth', function () {
    $plain = Str::random(64);
    $apiKey = StudioApiKey::factory()->create([
        'key' => hash('sha256', $plain),
        'permissions' => ['*' => ['index']],
        'last_used_at' => null,
    ]);

    $request = makeRequest('/api/studio/posts', 'GET', ['collection_slug' => 'posts']);
    $request->headers->set('X-Api-Key', $plain);

    $middleware = new ValidateApiKey;
    $middleware->handle($request, fn () => new Response('ok'), 'index');

    expect($apiKey->fresh()->last_used_at)->not->toBeNull();
});
