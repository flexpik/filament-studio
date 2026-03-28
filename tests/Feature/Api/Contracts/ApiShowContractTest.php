<?php

use Flexpik\FilamentStudio\Api\StudioApiRouteRegistrar;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Support\Str;

beforeEach(function () {
    StudioApiRouteRegistrar::register();

    $this->collection = StudioCollection::factory()->apiEnabled()->create(['slug' => 'articles']);

    $this->titleField = StudioField::factory()->required()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 1,
    ]);

    $this->viewsField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'views',
        'label' => 'Views',
        'field_type' => 'integer',
        'eav_cast' => 'integer',
        'sort_order' => 2,
    ]);

    EavQueryBuilder::invalidateFieldCache($this->collection->id);

    $this->plainKey = Str::random(64);
    StudioApiKey::factory()->fullAccess()->create([
        'key' => hash('sha256', $this->plainKey),
    ]);
});

function showHeaders(string $key): array
{
    return [
        'X-Api-Key' => $key,
        'Accept' => 'application/json',
    ];
}

it('returns single resource with full response schema', function () {
    $record = EavQueryBuilder::for($this->collection)->create(['title' => 'My Article', 'views' => 42]);

    $response = $this->getJson(
        "/api/studio/articles/{$record->uuid}",
        showHeaders($this->plainKey),
    );

    $response->assertOk();

    // Single resource is NOT wrapped in a top-level 'data' key (unlike list endpoint)
    $response->assertJsonStructure([
        'uuid',
        'data',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ]);

    // Verify there is no wrapping 'data' key at top level
    expect($response->json())->toHaveKeys(['uuid', 'data', 'created_by', 'updated_by', 'created_at', 'updated_at']);

    expect($response->json('uuid'))->toBe($record->uuid);
    expect($response->json('created_at'))->toBeString();
    expect($response->json('updated_at'))->toBeString();
});

it('returns 404 with JSON error structure for non-existent UUID', function () {
    $fakeUuid = Str::uuid()->toString();

    $response = $this->getJson(
        "/api/studio/articles/{$fakeUuid}",
        showHeaders($this->plainKey),
    );

    $response->assertNotFound();
    $response->assertJsonStructure(['message']);
});

it('returns 404 for soft-deleted records', function () {
    $record = EavQueryBuilder::for($this->collection)->create(['title' => 'To Be Deleted', 'views' => 0]);

    // Soft-delete the record
    StudioRecord::query()->where('uuid', $record->uuid)->first()->delete();

    $response = $this->getJson(
        "/api/studio/articles/{$record->uuid}",
        showHeaders($this->plainKey),
    );

    $response->assertNotFound();
    $response->assertJsonStructure(['message']);
});

it('returns data values matching what was stored', function () {
    $record = EavQueryBuilder::for($this->collection)->create(['title' => 'Exact Match', 'views' => 99]);

    $response = $this->getJson(
        "/api/studio/articles/{$record->uuid}",
        showHeaders($this->plainKey),
    );

    $response->assertOk();

    expect($response->json('data.title'))->toBeString()->toBe('Exact Match');
    expect($response->json('data.views'))->toBeInt()->toBe(99);
});
