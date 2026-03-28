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

function authHeaders(string $key): array
{
    return [
        'X-Api-Key' => $key,
        'Accept' => 'application/json',
    ];
}

it('rejects requests without an API key', function () {
    $response = $this->getJson('/api/studio/posts', ['Accept' => 'application/json']);

    $response->assertUnauthorized();
    $response->assertJsonPath('message', 'API key required. Provide it via the X-Api-Key header.');
});

it('rejects requests with an invalid API key', function () {
    $response = $this->getJson('/api/studio/posts', authHeaders(Str::random(64)));

    $response->assertUnauthorized();
    $response->assertJsonPath('message', 'Invalid API key.');
});

it('rejects an inactive API key', function () {
    $plainKey = Str::random(64);

    StudioApiKey::factory()->inactive()->fullAccess()->create([
        'key' => hash('sha256', $plainKey),
    ]);

    // findByKey filters by is_active = true, so inactive keys return 401
    $response = $this->getJson('/api/studio/posts', authHeaders($plainKey));

    $response->assertUnauthorized();
    $response->assertJsonPath('message', 'Invalid API key.');
});

it('rejects an expired API key', function () {
    $plainKey = Str::random(64);

    StudioApiKey::factory()->fullAccess()->create([
        'key' => hash('sha256', $plainKey),
        'expires_at' => now()->subDay(),
    ]);

    // Key is found (is_active = true) but can() returns false due to expiry → 403
    $response = $this->getJson('/api/studio/posts', authHeaders($plainKey));

    $response->assertForbidden();
    $response->assertJsonPath('message', 'This API key does not have permission to perform this action.');
});

it('allows a read-only key to list records', function () {
    $plainKey = Str::random(64);

    StudioApiKey::factory()->withPermissions([
        '*' => ['index', 'show'],
    ])->create([
        'key' => hash('sha256', $plainKey),
    ]);

    $response = $this->getJson('/api/studio/posts', authHeaders($plainKey));

    $response->assertOk();
});

it('rejects a read-only key when creating a record', function () {
    $plainKey = Str::random(64);

    StudioApiKey::factory()->withPermissions([
        '*' => ['index', 'show'],
    ])->create([
        'key' => hash('sha256', $plainKey),
    ]);

    $response = $this->postJson('/api/studio/posts', [
        'data' => ['title' => 'New Post'],
    ], authHeaders($plainKey));

    $response->assertForbidden();
});

it('scopes permissions per collection — key for tasks cannot access posts', function () {
    $plainKey = Str::random(64);

    StudioApiKey::factory()->withPermissions([
        'tasks' => ['index', 'show', 'store', 'update', 'destroy'],
    ])->create([
        'key' => hash('sha256', $plainKey),
    ]);

    $response = $this->getJson('/api/studio/posts', authHeaders($plainKey));

    $response->assertForbidden();
});

it('scopes permissions per collection — key for tasks can access tasks', function () {
    $plainKey = Str::random(64);

    StudioApiKey::factory()->withPermissions([
        'tasks' => ['index', 'show', 'store', 'update', 'destroy'],
    ])->create([
        'key' => hash('sha256', $plainKey),
    ]);

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

    $response = $this->getJson('/api/studio/tasks', authHeaders($plainKey));

    $response->assertOk();
});
