<?php

use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Illuminate\Support\Str;

it('creates an API key with a hashed key', function () {
    $plainKey = Str::random(64);
    $apiKey = StudioApiKey::create([
        'name' => 'Test Key',
        'key' => hash('sha256', $plainKey),
        'permissions' => [],
        'is_active' => true,
    ]);

    expect($apiKey)->toBeInstanceOf(StudioApiKey::class);
    expect($apiKey->name)->toBe('Test Key');
    expect($apiKey->is_active)->toBeTrue();
    expect($apiKey->permissions)->toBeArray();
});

it('can check permission for a collection action', function () {
    $collection = StudioCollection::factory()->create(['slug' => 'posts']);

    $apiKey = StudioApiKey::factory()->create([
        'permissions' => [
            'posts' => ['index', 'show'],
        ],
    ]);

    expect($apiKey->can('posts', ApiAction::Index))->toBeTrue();
    expect($apiKey->can('posts', ApiAction::Show))->toBeTrue();
    expect($apiKey->can('posts', ApiAction::Store))->toBeFalse();
    expect($apiKey->can('posts', ApiAction::Destroy))->toBeFalse();
});

it('can check wildcard permission', function () {
    $apiKey = StudioApiKey::factory()->create([
        'permissions' => [
            '*' => ['index', 'show', 'store', 'update', 'destroy'],
        ],
    ]);

    expect($apiKey->can('any-collection', ApiAction::Index))->toBeTrue();
    expect($apiKey->can('any-collection', ApiAction::Destroy))->toBeTrue();
});

it('returns false when inactive', function () {
    $apiKey = StudioApiKey::factory()->create([
        'is_active' => false,
        'permissions' => [
            '*' => ['index', 'show', 'store', 'update', 'destroy'],
        ],
    ]);

    expect($apiKey->can('posts', ApiAction::Index))->toBeFalse();
});

it('scopes by tenant', function () {
    StudioApiKey::factory()->create(['tenant_id' => 1]);
    StudioApiKey::factory()->create(['tenant_id' => 2]);
    StudioApiKey::factory()->create(['tenant_id' => null]);

    expect(StudioApiKey::query()->forTenant(1)->count())->toBe(1);
    expect(StudioApiKey::query()->forTenant(null)->count())->toBe(3);
});

it('finds key by plain text token', function () {
    $plain = Str::random(64);
    StudioApiKey::factory()->create([
        'key' => hash('sha256', $plain),
        'is_active' => true,
    ]);

    $found = StudioApiKey::findByKey($plain);
    expect($found)->not->toBeNull();
    expect($found->is_active)->toBeTrue();
});
