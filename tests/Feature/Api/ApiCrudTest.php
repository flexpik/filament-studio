<?php

use Flexpik\FilamentStudio\Api\StudioApiRouteRegistrar;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Support\Str;

beforeEach(function () {
    // Register API routes manually since config is set after boot
    StudioApiRouteRegistrar::register();

    $this->collection = StudioCollection::factory()->apiEnabled()->create(['slug' => 'posts']);

    $this->titleField = StudioField::factory()->required()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 1,
    ]);

    $this->bodyField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'body',
        'label' => 'Body',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 2,
    ]);

    // Invalidate field cache so tests get fresh data
    EavQueryBuilder::invalidateFieldCache($this->collection->id);

    $this->plainKey = Str::random(64);
    StudioApiKey::factory()->fullAccess()->create([
        'key' => hash('sha256', $this->plainKey),
    ]);
});

function apiHeaders(string $key): array
{
    return [
        'X-Api-Key' => $key,
        'Accept' => 'application/json',
    ];
}

it('lists records for a collection', function () {
    EavQueryBuilder::for($this->collection)->create(['title' => 'First Post', 'body' => 'Content']);
    EavQueryBuilder::for($this->collection)->create(['title' => 'Second Post', 'body' => 'More content']);

    $response = $this->getJson('/api/studio/posts', apiHeaders($this->plainKey));

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
});

it('shows a single record by uuid', function () {
    $record = EavQueryBuilder::for($this->collection)->create(['title' => 'My Post', 'body' => 'Body text']);

    $response = $this->getJson("/api/studio/posts/{$record->uuid}", apiHeaders($this->plainKey));

    $response->assertOk();
    $response->assertJsonPath('data.uuid', $record->uuid);
});

it('creates a record with valid data', function () {
    $response = $this->postJson('/api/studio/posts', [
        'data' => [
            'title' => 'New Post',
            'body' => 'Some body',
        ],
    ], apiHeaders($this->plainKey));

    $response->assertStatus(201);
    $response->assertJsonPath('data.data.title', 'New Post');

    expect(StudioRecord::query()->where('collection_id', $this->collection->id)->count())->toBe(1);
});

it('updates a record', function () {
    $record = EavQueryBuilder::for($this->collection)->create(['title' => 'Old Title', 'body' => 'Old Body']);

    $response = $this->putJson("/api/studio/posts/{$record->uuid}", [
        'data' => [
            'title' => 'Updated Title',
        ],
    ], apiHeaders($this->plainKey));

    $response->assertOk();
    $response->assertJsonPath('data.data.title', 'Updated Title');
});

it('deletes a record', function () {
    $record = EavQueryBuilder::for($this->collection)->create(['title' => 'To Delete', 'body' => 'Body']);

    $response = $this->deleteJson("/api/studio/posts/{$record->uuid}", [], apiHeaders($this->plainKey));

    $response->assertStatus(204);
});

it('returns 404 for unknown collection', function () {
    $response = $this->getJson('/api/studio/nonexistent', apiHeaders($this->plainKey));

    $response->assertStatus(404);
});

it('returns 404 for unknown record uuid', function () {
    $response = $this->getJson('/api/studio/posts/'.Str::uuid(), apiHeaders($this->plainKey));

    $response->assertStatus(404);
});

it('paginates records with per_page and page', function () {
    for ($i = 1; $i <= 5; $i++) {
        EavQueryBuilder::for($this->collection)->create(['title' => "Post {$i}"]);
    }

    $response = $this->getJson('/api/studio/posts?per_page=2&page=1', apiHeaders($this->plainKey));

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonPath('meta.per_page', 2);

    $response2 = $this->getJson('/api/studio/posts?per_page=2&page=3', apiHeaders($this->plainKey));

    $response2->assertOk();
    $response2->assertJsonCount(1, 'data');
});

it('returns validation errors for missing required fields on store', function () {
    $response = $this->postJson('/api/studio/posts', [
        'data' => [
            'body' => 'No title provided',
        ],
    ], apiHeaders($this->plainKey));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.title']);
});
