<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Support\ResolveStudioApiKeyFromEnv;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
use Flexpik\FilamentStudio\Models\StudioApiKey;

it('resolves a key from STUDIO_API_KEY env and binds it to the context', function () {
    $key = StudioApiKey::factory()->create(['key' => 'sk_env_test', 'is_active' => true]);

    putenv('STUDIO_API_KEY=sk_env_test');

    app(ResolveStudioApiKeyFromEnv::class)->resolve();

    expect(app(StudioApiKeyContext::class)->current()?->id)->toBe($key->id);

    putenv('STUDIO_API_KEY');
});

it('throws when STUDIO_API_KEY is missing', function () {
    putenv('STUDIO_API_KEY');

    expect(fn () => app(ResolveStudioApiKeyFromEnv::class)->resolve())
        ->toThrow(RuntimeException::class, 'STUDIO_API_KEY environment variable is not set');
});

it('throws when STUDIO_API_KEY does not match any key', function () {
    putenv('STUDIO_API_KEY=sk_doesnt_exist');

    expect(fn () => app(ResolveStudioApiKeyFromEnv::class)->resolve())
        ->toThrow(RuntimeException::class, 'invalid');

    putenv('STUDIO_API_KEY');
});

it('throws when the resolved key is inactive', function () {
    StudioApiKey::factory()->create(['key' => 'sk_inactive_env', 'is_active' => false]);

    putenv('STUDIO_API_KEY=sk_inactive_env');

    expect(fn () => app(ResolveStudioApiKeyFromEnv::class)->resolve())
        ->toThrow(RuntimeException::class, 'inactive');

    putenv('STUDIO_API_KEY');
});
