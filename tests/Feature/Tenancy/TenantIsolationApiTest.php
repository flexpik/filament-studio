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

    // Tenant A setup
    $this->collectionA = StudioCollection::factory()->forTenant(1)->create(['slug' => 'articles']);

    StudioField::factory()->required()->create([
        'collection_id' => $this->collectionA->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 1,
    ]);

    EavQueryBuilder::invalidateFieldCache($this->collectionA->id);

    $this->recordA = EavQueryBuilder::for($this->collectionA)->create(['title' => 'Tenant A Article']);

    // Tenant B setup (same slug, different tenant)
    $this->collectionB = StudioCollection::factory()->forTenant(2)->create(['slug' => 'articles']);

    StudioField::factory()->required()->create([
        'collection_id' => $this->collectionB->id,
        'tenant_id' => 2,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 1,
    ]);

    EavQueryBuilder::invalidateFieldCache($this->collectionB->id);

    $this->recordB = EavQueryBuilder::for($this->collectionB)->create(['title' => 'Tenant B Article']);

    // API key for tenant A
    $this->plainKeyA = Str::random(64);
    StudioApiKey::factory()->fullAccess()->create([
        'key' => hash('sha256', $this->plainKeyA),
        'tenant_id' => 1,
    ]);

    // API key for tenant B
    $this->plainKeyB = Str::random(64);
    StudioApiKey::factory()->fullAccess()->create([
        'key' => hash('sha256', $this->plainKeyB),
        'tenant_id' => 2,
    ]);
});

function tenantApiHeaders(string $key): array
{
    return [
        'X-Api-Key' => $key,
        'Accept' => 'application/json',
    ];
}

it('prevents an API key for tenant A from listing records in tenant B collection', function () {
    // Tenant A key should only see tenant A's collection
    $responseA = $this->getJson('/api/studio/articles', tenantApiHeaders($this->plainKeyA));
    $responseA->assertOk();
    $responseA->assertJsonCount(1, 'data');

    // Tenant B key should only see tenant B's collection
    $responseB = $this->getJson('/api/studio/articles', tenantApiHeaders($this->plainKeyB));
    $responseB->assertOk();
    $responseB->assertJsonCount(1, 'data');

    // Verify the records belong to the correct tenant's collection
    $dataA = $responseA->json('data.0.data.title');
    $dataB = $responseB->json('data.0.data.title');

    expect($dataA)->toBe('Tenant A Article')
        ->and($dataB)->toBe('Tenant B Article');
});

it('prevents an API key for tenant A from showing a specific record belonging to tenant B', function () {
    // Tenant A key trying to access tenant B's record via tenant B's collection
    // Since the collection resolves to tenant A's version, the UUID won't be found
    $response = $this->getJson(
        "/api/studio/articles/{$this->recordB->uuid}",
        tenantApiHeaders($this->plainKeyA),
    );

    $response->assertStatus(404);
});

it('prevents an API key for tenant A from creating records in tenant B collection', function () {
    // Tenant A key creates a record — it should go into tenant A's collection
    $response = $this->postJson('/api/studio/articles', [
        'data' => ['title' => 'New Article from A'],
    ], tenantApiHeaders($this->plainKeyA));

    $response->assertStatus(201);

    // Verify the record was created in tenant A's collection, not tenant B's
    $recordCountA = StudioRecord::where('collection_id', $this->collectionA->id)->count();
    $recordCountB = StudioRecord::where('collection_id', $this->collectionB->id)->count();

    expect($recordCountA)->toBe(2)  // original + new
        ->and($recordCountB)->toBe(1); // unchanged
});

it('prevents an API key for tenant A from updating a record in tenant B collection', function () {
    // Tenant A key trying to update tenant B's record
    $response = $this->putJson(
        "/api/studio/articles/{$this->recordB->uuid}",
        ['data' => ['title' => 'Hijacked']],
        tenantApiHeaders($this->plainKeyA),
    );

    $response->assertStatus(404);

    // Verify tenant B's record is unchanged
    $this->recordB->refresh();
    $values = EavQueryBuilder::for($this->collectionB)
        ->select(['title'])
        ->get();

    expect($values->first()->title)->toBe('Tenant B Article');
});

it('prevents an API key for tenant A from deleting a record in tenant B collection', function () {
    // Tenant A key trying to delete tenant B's record
    $response = $this->deleteJson(
        "/api/studio/articles/{$this->recordB->uuid}",
        [],
        tenantApiHeaders($this->plainKeyA),
    );

    $response->assertStatus(404);

    // Verify tenant B's record still exists
    $recordCountB = StudioRecord::where('collection_id', $this->collectionB->id)
        ->whereNull('deleted_at')
        ->count();

    expect($recordCountB)->toBe(1);
});
