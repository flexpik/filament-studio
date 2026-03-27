<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioMigrationLog;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioRecordVersion;

it('isolates record versions so tenant A versions are not visible when querying via forTenant(2)', function () {
    $collectionA = StudioCollection::factory()->forTenant(1)->create(['slug' => 'products']);
    $collectionB = StudioCollection::factory()->forTenant(2)->create(['slug' => 'products']);

    $recordA = StudioRecord::factory()->forTenant(1)->create([
        'collection_id' => $collectionA->id,
    ]);

    StudioRecordVersion::factory()->count(3)->create([
        'record_id' => $recordA->id,
        'collection_id' => $collectionA->id,
        'tenant_id' => 1,
    ]);

    $recordB = StudioRecord::factory()->forTenant(2)->create([
        'collection_id' => $collectionB->id,
    ]);

    StudioRecordVersion::factory()->create([
        'record_id' => $recordB->id,
        'collection_id' => $collectionB->id,
        'tenant_id' => 2,
    ]);

    $versionsTenantA = StudioRecordVersion::forTenant(1)->get();
    $versionsTenantB = StudioRecordVersion::forTenant(2)->get();

    expect($versionsTenantA)->toHaveCount(3)
        ->and($versionsTenantA->pluck('tenant_id')->unique()->toArray())->toBe([1])
        ->and($versionsTenantB)->toHaveCount(1)
        ->and($versionsTenantB->first()->tenant_id)->toBe(2);
});

it('isolates record versions so querying with wrong tenant scope returns nothing even knowing the record id', function () {
    $collection = StudioCollection::factory()->forTenant(1)->create();

    $record = StudioRecord::factory()->forTenant(1)->create([
        'collection_id' => $collection->id,
    ]);

    StudioRecordVersion::factory()->count(2)->create([
        'record_id' => $record->id,
        'collection_id' => $collection->id,
        'tenant_id' => 1,
    ]);

    // Tenant 2 tries to query versions for tenant 1's record by record_id
    $crossTenantVersions = StudioRecordVersion::where('record_id', $record->id)
        ->forTenant(2)
        ->get();

    expect($crossTenantVersions)->toHaveCount(0);

    // Confirm tenant 1 can see them
    $ownVersions = StudioRecordVersion::where('record_id', $record->id)
        ->forTenant(1)
        ->get();

    expect($ownVersions)->toHaveCount(2);
});

it('isolates migration logs so tenant A logs are not visible to tenant B via forTenant scope', function () {
    $collectionA = StudioCollection::factory()->forTenant(1)->create();
    $collectionB = StudioCollection::factory()->forTenant(2)->create();

    StudioMigrationLog::factory()->count(2)->create([
        'collection_id' => $collectionA->id,
        'tenant_id' => 1,
        'operation' => 'create_collection',
    ]);

    StudioMigrationLog::factory()->create([
        'collection_id' => $collectionB->id,
        'tenant_id' => 2,
        'operation' => 'add_field',
    ]);

    $logsTenantA = StudioMigrationLog::forTenant(1)->get();
    $logsTenantB = StudioMigrationLog::forTenant(2)->get();

    expect($logsTenantA)->toHaveCount(2)
        ->and($logsTenantA->pluck('tenant_id')->unique()->toArray())->toBe([1])
        ->and($logsTenantB)->toHaveCount(1)
        ->and($logsTenantB->first()->tenant_id)->toBe(2)
        ->and($logsTenantB->first()->operation)->toBe('add_field');
});

it('isolates migration logs so tenant A collection field logs are invisible to tenant B', function () {
    $collectionA = StudioCollection::factory()->forTenant(1)->create();

    $fieldA = StudioField::factory()->create([
        'collection_id' => $collectionA->id,
        'tenant_id' => 1,
        'column_name' => 'secret_field',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    StudioMigrationLog::factory()->create([
        'collection_id' => $collectionA->id,
        'field_id' => $fieldA->id,
        'tenant_id' => 1,
        'operation' => 'add_field',
        'after_state' => ['column_name' => 'secret_field', 'field_type' => 'text'],
    ]);

    StudioMigrationLog::factory()->create([
        'collection_id' => $collectionA->id,
        'field_id' => $fieldA->id,
        'tenant_id' => 1,
        'operation' => 'update_field',
        'before_state' => ['column_name' => 'secret_field'],
        'after_state' => ['column_name' => 'secret_field', 'field_type' => 'textarea'],
    ]);

    // Tenant B queries migration logs — should see nothing
    $tenantBLogs = StudioMigrationLog::where('field_id', $fieldA->id)
        ->forTenant(2)
        ->get();

    expect($tenantBLogs)->toHaveCount(0);

    // Tenant A can see both logs
    $tenantALogs = StudioMigrationLog::where('field_id', $fieldA->id)
        ->forTenant(1)
        ->get();

    expect($tenantALogs)->toHaveCount(2);
});
