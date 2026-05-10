<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Support\StudioScope;
use Flexpik\FilamentStudio\Models\StudioApiKey;

it('returns true when the _studio array contains the scope name', function () {
    $key = StudioApiKey::factory()->create([
        'is_active' => true,
        'permissions' => ['_studio' => ['manage_collections', 'read_schema']],
    ]);

    expect($key->canManage(StudioScope::ManageCollections))->toBeTrue();
    expect($key->canManage(StudioScope::ReadSchema))->toBeTrue();
});

it('returns false when the scope is missing', function () {
    $key = StudioApiKey::factory()->create([
        'is_active' => true,
        'permissions' => ['_studio' => ['read_schema']],
    ]);

    expect($key->canManage(StudioScope::ManageCollections))->toBeFalse();
});

it('returns false when there is no _studio key', function () {
    $key = StudioApiKey::factory()->create([
        'is_active' => true,
        'permissions' => ['products' => ['index']],
    ]);

    expect($key->canManage(StudioScope::ManageCollections))->toBeFalse();
});

it('returns false when the key is inactive', function () {
    $key = StudioApiKey::factory()->create([
        'is_active' => false,
        'permissions' => ['_studio' => ['manage_collections']],
    ]);

    expect($key->canManage(StudioScope::ManageCollections))->toBeFalse();
});

it('returns false when the key is expired', function () {
    $key = StudioApiKey::factory()->create([
        'is_active' => true,
        'expires_at' => now()->subDay(),
        'permissions' => ['_studio' => ['manage_collections']],
    ]);

    expect($key->canManage(StudioScope::ManageCollections))->toBeFalse();
});
