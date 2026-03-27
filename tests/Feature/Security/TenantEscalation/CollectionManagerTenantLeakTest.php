<?php

// tests/Feature/Security/TenantEscalation/CollectionManagerTenantLeakTest.php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

beforeEach(function () {
    EavQueryBuilder::invalidateFieldCache();
});

it('does not return collections belonging to another tenant in CollectionManager table query', function () {
    $collectionA = StudioCollection::factory()->forTenant(1)->create([
        'name' => 'Tenant A Products',
        'slug' => 'tenant-a-products',
    ]);

    $collectionB = StudioCollection::factory()->forTenant(2)->create([
        'name' => 'Tenant B Secrets',
        'slug' => 'tenant-b-secrets',
    ]);

    $visibleToTenantA = StudioCollection::query()
        ->forTenant(1)
        ->get();

    expect($visibleToTenantA)->toHaveCount(1)
        ->and($visibleToTenantA->first()->id)->toBe($collectionA->id)
        ->and($visibleToTenantA->pluck('slug')->toArray())->not->toContain('tenant-b-secrets');
});

it('does not allow Tenant A to access Tenant B collection by direct ID in CollectionManager', function () {
    $collectionB = StudioCollection::factory()->forTenant(2)->create();

    $result = StudioCollection::query()
        ->forTenant(1)
        ->find($collectionB->id);

    expect($result)->toBeNull();
});

it('CollectionManagerResource table query must scope by tenant', function () {
    $collectionA = StudioCollection::factory()->forTenant(1)->create();
    $collectionB = StudioCollection::factory()->forTenant(2)->create();

    $unscoped = StudioCollection::query()->get();
    expect($unscoped)->toHaveCount(2);

    $scoped = StudioCollection::query()->forTenant(1)->get();
    expect($scoped)->toHaveCount(1)
        ->and($scoped->first()->id)->toBe($collectionA->id);
});
