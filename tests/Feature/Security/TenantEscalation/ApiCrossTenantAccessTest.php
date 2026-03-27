<?php

// tests/Feature/Security/TenantEscalation/ApiCrossTenantAccessTest.php

use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

beforeEach(function () {
    EavQueryBuilder::invalidateFieldCache();
});

it('API key from Tenant A cannot list Tenant B records via shared collection slug', function () {
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

    EavQueryBuilder::for($colA)->tenant(1)->create(['name' => 'Tenant A Product']);
    EavQueryBuilder::for($colB)->tenant(2)->create(['name' => 'Tenant B Secret']);

    $tenantARecords = EavQueryBuilder::for($colA)->tenant(1)->get();
    expect($tenantARecords)->toHaveCount(1);

    $crossTenantAttempt = EavQueryBuilder::for($colB)->tenant(1)->get();
    expect($crossTenantAttempt)->toHaveCount(0);
});
