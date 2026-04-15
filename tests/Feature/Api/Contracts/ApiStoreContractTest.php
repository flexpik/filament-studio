<?php

use Flexpik\FilamentStudio\Api\StudioApiRouteRegistrar;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Support\Str;

beforeEach(function () {
    StudioApiRouteRegistrar::register();

    $this->collection = StudioCollection::factory()->apiEnabled()->create(['slug' => 'products']);

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

function storeHeaders(string $key): array
{
    return [
        'X-Api-Key' => $key,
        'Accept' => 'application/json',
    ];
}

it('returns 201 with created record resource schema on success', function () {
    $response = $this->postJson('/api/studio/products', [
        'data' => [
            'name' => 'Widget',
            'quantity' => 10,
        ],
    ], storeHeaders($this->plainKey));

    $response->assertStatus(201);

    $response->assertJsonStructure([
        'data' => [
            'uuid',
            'data',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
        ],
        '_meta' => ['locale'],
    ]);

    expect($response->json('data.uuid'))->toBeString()->not->toBeEmpty();
    expect($response->json('data.data.name'))->toBe('Widget');
    expect($response->json('data.data.quantity'))->toBe(10);
    expect($response->json('data.created_at'))->toBeString();
    expect($response->json('data.updated_at'))->toBeString();
});

it('returns 422 with field-level error structure when validation fails', function () {
    $response = $this->postJson('/api/studio/products', [
        'data' => [
            'quantity' => 5,
        ],
    ], storeHeaders($this->plainKey));

    $response->assertStatus(422);

    $response->assertJsonStructure([
        'message',
        'errors' => [
            'data.name',
        ],
    ]);

    expect($response->json('message'))->toBeString()->not->toBeEmpty();
    expect($response->json('errors'))->toHaveKey('data.name');
    expect($response->json('errors')['data.name'])->toBeArray()->not->toBeEmpty();
});

it('enforces required fields via API validation', function () {
    // Omit the required 'name' field entirely
    $response = $this->postJson('/api/studio/products', [
        'data' => [],
    ], storeHeaders($this->plainKey));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.name']);
});

it('returns 422 when sending a non-numeric string to an integer field', function () {
    $response = $this->postJson('/api/studio/products', [
        'data' => [
            'name' => 'Widget',
            'quantity' => 'not-a-number',
        ],
    ], storeHeaders($this->plainKey));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['data.quantity']);
});

it('allows optional fields to be omitted from the request body', function () {
    // Only send the required 'name' field, omit optional 'quantity' and 'description'
    $response = $this->postJson('/api/studio/products', [
        'data' => [
            'name' => 'Minimal Product',
        ],
    ], storeHeaders($this->plainKey));

    $response->assertStatus(201);

    expect($response->json('data.uuid'))->toBeString()->not->toBeEmpty();
    expect($response->json('data.data.name'))->toBe('Minimal Product');
});
