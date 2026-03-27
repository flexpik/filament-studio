<?php

use Flexpik\FilamentStudio\Api\StudioApiRouteRegistrar;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecordVersion;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Support\Str;

beforeEach(function () {
    StudioApiRouteRegistrar::register();

    $this->collection = StudioCollection::factory()->create(['slug' => 'products']);

    $this->nameField = StudioField::factory()->required()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'name',
        'label' => 'Name',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 1,
    ]);

    $this->quantityField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'quantity',
        'label' => 'Quantity',
        'field_type' => 'integer',
        'eav_cast' => 'integer',
        'sort_order' => 2,
        'is_required' => false,
    ]);

    $this->descriptionField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'description',
        'label' => 'Description',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 3,
        'is_required' => false,
    ]);

    EavQueryBuilder::invalidateFieldCache($this->collection->id);

    $this->plainKey = Str::random(64);
    StudioApiKey::factory()->fullAccess()->create([
        'key' => hash('sha256', $this->plainKey),
    ]);
});

function updateHeaders(string $key): array
{
    return [
        'X-Api-Key' => $key,
        'Accept' => 'application/json',
    ];
}

it('returns 200 with updated resource schema on success', function () {
    $record = EavQueryBuilder::for($this->collection)->create([
        'name' => 'Original Widget',
        'quantity' => 5,
    ]);

    $response = $this->putJson(
        "/api/studio/products/{$record->uuid}",
        ['data' => ['name' => 'Updated Widget', 'quantity' => 20]],
        updateHeaders($this->plainKey),
    );

    $response->assertOk();

    $response->assertJsonStructure([
        'uuid',
        'data',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ]);

    expect($response->json('uuid'))->toBe($record->uuid);
    expect($response->json('data.name'))->toBe('Updated Widget');
    expect($response->json('data.quantity'))->toBe(20);
});

it('performs partial update when sending only one field', function () {
    $record = EavQueryBuilder::for($this->collection)->create([
        'name' => 'Partial Widget',
        'quantity' => 10,
        'description' => 'Original description',
    ]);

    $response = $this->putJson(
        "/api/studio/products/{$record->uuid}",
        ['data' => ['quantity' => 99]],
        updateHeaders($this->plainKey),
    );

    $response->assertOk();

    // Updated field should reflect the new value
    expect($response->json('data.quantity'))->toBe(99);

    // Unchanged fields should retain their original values
    expect($response->json('data.name'))->toBe('Partial Widget');
    expect($response->json('data.description'))->toBe('Original description');
});

it('creates a version snapshot when collection has versioning enabled', function () {
    $this->collection->update(['enable_versioning' => true]);

    $record = EavQueryBuilder::for($this->collection)->create([
        'name' => 'Versioned Widget',
        'quantity' => 1,
    ]);

    // Clear any versions created during the initial create
    StudioRecordVersion::query()->where('record_id', $record->id)->delete();

    $response = $this->putJson(
        "/api/studio/products/{$record->uuid}",
        ['data' => ['name' => 'Versioned Widget v2']],
        updateHeaders($this->plainKey),
    );

    $response->assertOk();

    $versions = StudioRecordVersion::query()
        ->where('record_id', $record->id)
        ->get();

    expect($versions)->not->toBeEmpty();
});

it('returns 404 for non-existent record UUID', function () {
    $fakeUuid = Str::uuid()->toString();

    $response = $this->putJson(
        "/api/studio/products/{$fakeUuid}",
        ['data' => ['name' => 'Ghost']],
        updateHeaders($this->plainKey),
    );

    $response->assertNotFound();
    $response->assertJsonStructure(['message']);
});

it('returns 422 when sending invalid type for a field', function () {
    $record = EavQueryBuilder::for($this->collection)->create([
        'name' => 'Validate Widget',
        'quantity' => 5,
    ]);

    $response = $this->putJson(
        "/api/studio/products/{$record->uuid}",
        ['data' => ['quantity' => 'not-a-number']],
        updateHeaders($this->plainKey),
    );

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.quantity']);
});
