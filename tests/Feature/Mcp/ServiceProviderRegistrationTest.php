<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Support\ResolveStudioApiKey;
use Illuminate\Support\Facades\Route;

it('registers the MCP HTTP route when mcp.enabled and mcp.http.enabled', function () {
    config()->set('filament-studio.mcp.enabled', true);
    config()->set('filament-studio.mcp.http.enabled', true);
    config()->set('filament-studio.mcp.http.prefix', 'ai/studio');

    require __DIR__.'/../../../src/Mcp/Routes.php';

    $registered = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'ai/studio'));

    expect($registered)->not->toBeEmpty();

    $route = $registered->first();
    expect($route->middleware())->toContain(ResolveStudioApiKey::class);
    expect($route->middleware())->toContain('throttle:studio-mcp');
});
