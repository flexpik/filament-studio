<?php

// tests/Feature/Concurrency/ConcurrentSameTenantCreateTest.php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

beforeEach(function () {
    EavQueryBuilder::invalidateFieldCache();
});

it('two rapid creates in same collection produce distinct records with no data loss', function () {
    $collection = StudioCollection::factory()->forTenant(1)->create();
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $recordA = EavQueryBuilder::for($collection)->tenant(1)->create(['title' => 'Record A']);
    $recordB = EavQueryBuilder::for($collection)->tenant(1)->create(['title' => 'Record B']);

    expect($recordA->uuid)->not->toBe($recordB->uuid)
        ->and(StudioRecord::where('collection_id', $collection->id)->count())->toBe(2);

    $dataA = EavQueryBuilder::for($collection)->tenant(1)->getRecordData($recordA);
    $dataB = EavQueryBuilder::for($collection)->tenant(1)->getRecordData($recordB);

    expect($dataA['title'])->toBe('Record A')
        ->and($dataB['title'])->toBe('Record B');
});

it('concurrent creates from different tenants in same-slug collections remain isolated', function () {
    $colA = StudioCollection::factory()->forTenant(1)->create(['slug' => 'products']);
    $colB = StudioCollection::factory()->forTenant(2)->create(['slug' => 'products']);

    StudioField::factory()->create([
        'collection_id' => $colA->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);
    StudioField::factory()->create([
        'collection_id' => $colB->id,
        'tenant_id' => 2,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $recA = EavQueryBuilder::for($colA)->tenant(1)->create(['name' => 'Tenant A Product']);
    $recB = EavQueryBuilder::for($colB)->tenant(2)->create(['name' => 'Tenant B Product']);

    $tenantARecords = EavQueryBuilder::for($colA)->tenant(1)->get();
    $tenantBRecords = EavQueryBuilder::for($colB)->tenant(2)->get();

    expect($tenantARecords)->toHaveCount(1)
        ->and($tenantBRecords)->toHaveCount(1)
        ->and($tenantARecords->first()->id)->not->toBe($tenantBRecords->first()->id);
});
