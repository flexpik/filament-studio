<?php

use Flexpik\FilamentStudio\Api\StudioApiRouteRegistrar;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Support\Str;

beforeEach(function () {
    StudioApiRouteRegistrar::register();

    $this->collection = StudioCollection::factory()->withSoftDeletes()->create(['slug' => 'posts']);

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

function destroyHeaders(string $key): array
{
    return [
        'X-Api-Key' => $key,
        'Accept' => 'application/json',
    ];
}

it('returns 204 No Content with empty body on successful delete', function () {
    $record = EavQueryBuilder::for($this->collection)->create(['title' => 'Delete Me', 'views' => 10]);

    $response = $this->deleteJson(
        "/api/studio/posts/{$record->uuid}",
        [],
        destroyHeaders($this->plainKey),
    );

    $response->assertNoContent();
    expect($response->getContent())->toBeEmpty();
});

it('soft-deletes the record so it has deleted_at set', function () {
    $record = EavQueryBuilder::for($this->collection)->create(['title' => 'Soft Delete Me', 'views' => 5]);

    $this->deleteJson(
        "/api/studio/posts/{$record->uuid}",
        [],
        destroyHeaders($this->plainKey),
    )->assertNoContent();

    // Record should not appear in normal queries
    expect(StudioRecord::query()->where('uuid', $record->uuid)->first())->toBeNull();

    // Record should still exist with trashed scope
    $trashedRecord = StudioRecord::withTrashed()->where('uuid', $record->uuid)->first();
    expect($trashedRecord)->not->toBeNull();
    expect($trashedRecord->deleted_at)->not->toBeNull();
});

it('returns 404 when deleting an already soft-deleted record', function () {
    $record = EavQueryBuilder::for($this->collection)->create(['title' => 'Already Gone', 'views' => 0]);

    // First delete
    $this->deleteJson(
        "/api/studio/posts/{$record->uuid}",
        [],
        destroyHeaders($this->plainKey),
    )->assertNoContent();

    // Second delete should 404 since the soft-deleted record is excluded
    $response = $this->deleteJson(
        "/api/studio/posts/{$record->uuid}",
        [],
        destroyHeaders($this->plainKey),
    );

    $response->assertNotFound();
    $response->assertJsonStructure(['message']);
});

it('returns 404 for a non-existent UUID', function () {
    $fakeUuid = Str::uuid()->toString();

    $response = $this->deleteJson(
        "/api/studio/posts/{$fakeUuid}",
        [],
        destroyHeaders($this->plainKey),
    );

    $response->assertNotFound();
    $response->assertJsonStructure(['message']);
});

it('preserves StudioValue rows after soft delete', function () {
    $record = EavQueryBuilder::for($this->collection)->create(['title' => 'Keep Values', 'views' => 42]);

    $valueCountBefore = StudioValue::where('record_id', $record->id)->count();
    expect($valueCountBefore)->toBeGreaterThan(0);

    $this->deleteJson(
        "/api/studio/posts/{$record->uuid}",
        [],
        destroyHeaders($this->plainKey),
    )->assertNoContent();

    // Values should still exist in the database after soft delete
    $valueCountAfter = StudioValue::where('record_id', $record->id)->count();
    expect($valueCountAfter)->toBe($valueCountBefore);
});
