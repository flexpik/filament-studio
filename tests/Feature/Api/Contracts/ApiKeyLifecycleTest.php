<?php

use Flexpik\FilamentStudio\Api\StudioApiRouteRegistrar;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Support\Str;

beforeEach(function () {
    StudioApiRouteRegistrar::register();

    $this->collection = StudioCollection::factory()->apiEnabled()->create(['slug' => 'posts']);

    StudioField::factory()->required()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 1,
    ]);

    EavQueryBuilder::invalidateFieldCache($this->collection->id);
});

function lifecycleHeaders(string $key): array
{
    return [
        'X-Api-Key' => $key,
        'Accept' => 'application/json',
    ];
}

it('allows requests after reactivating a deactivated key', function () {
    $plainKey = Str::random(64);

    $apiKey = StudioApiKey::factory()->fullAccess()->create([
        'key' => hash('sha256', $plainKey),
        'is_active' => true,
    ]);

    // Deactivate the key — requests should fail
    $apiKey->update(['is_active' => false]);

    $response = $this->getJson('/api/studio/posts', lifecycleHeaders($plainKey));
    $response->assertUnauthorized();

    // Reactivate the key — requests should succeed again
    $apiKey->update(['is_active' => true]);

    $response = $this->getJson('/api/studio/posts', lifecycleHeaders($plainKey));
    $response->assertOk();
});

it('accepts a key with a future expiry date', function () {
    $plainKey = Str::random(64);

    StudioApiKey::factory()->fullAccess()->create([
        'key' => hash('sha256', $plainKey),
        'expires_at' => now()->addYear(),
    ]);

    $response = $this->getJson('/api/studio/posts', lifecycleHeaders($plainKey));

    $response->assertOk();
});

it('applies updated permissions on the next request', function () {
    $plainKey = Str::random(64);

    $apiKey = StudioApiKey::factory()->withPermissions([
        '*' => ['index', 'show'],
    ])->create([
        'key' => hash('sha256', $plainKey),
    ]);

    // Read-only: store should be forbidden
    $response = $this->postJson('/api/studio/posts', [
        'data' => ['title' => 'New Post'],
    ], lifecycleHeaders($plainKey));

    $response->assertForbidden();

    // Upgrade to full access
    $apiKey->update([
        'permissions' => ['*' => ['index', 'show', 'store', 'update', 'destroy']],
    ]);

    // Store should now succeed
    $response = $this->postJson('/api/studio/posts', [
        'data' => ['title' => 'New Post'],
    ], lifecycleHeaders($plainKey));

    $response->assertCreated();
});

it('scopes key permissions to a specific collection and denies access to others', function () {
    $plainKey = Str::random(64);

    // Key with permissions only for the 'posts' collection
    StudioApiKey::factory()->withPermissions([
        'posts' => ['index', 'show', 'store', 'update', 'destroy'],
    ])->create([
        'key' => hash('sha256', $plainKey),
    ]);

    // Create a second collection
    $tasksCollection = StudioCollection::factory()->apiEnabled()->create(['slug' => 'tasks']);

    StudioField::factory()->required()->create([
        'collection_id' => $tasksCollection->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 1,
    ]);

    EavQueryBuilder::invalidateFieldCache($tasksCollection->id);

    // Should work for 'posts'
    $response = $this->getJson('/api/studio/posts', lifecycleHeaders($plainKey));
    $response->assertOk();

    // Should be forbidden for 'tasks'
    $response = $this->getJson('/api/studio/tasks', lifecycleHeaders($plainKey));
    $response->assertForbidden();
});
