<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioSavedFilter;
use Illuminate\Foundation\Auth\User;

it('isolates saved filters so tenant A filters are not visible via forTenant scope for tenant B', function () {
    $collectionA = StudioCollection::factory()->forTenant(1)->create(['slug' => 'products']);
    $collectionB = StudioCollection::factory()->forTenant(2)->create(['slug' => 'products']);

    $user = User::forceCreate([
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => bcrypt('password'),
    ]);

    StudioSavedFilter::create([
        'collection_id' => $collectionA->id,
        'created_by' => $user->id,
        'tenant_id' => 1,
        'name' => 'Tenant A Filter',
        'is_shared' => false,
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);

    StudioSavedFilter::create([
        'collection_id' => $collectionB->id,
        'created_by' => $user->id,
        'tenant_id' => 2,
        'name' => 'Tenant B Filter',
        'is_shared' => false,
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);

    $tenantAFilters = StudioSavedFilter::forTenant(1)->get();
    $tenantBFilters = StudioSavedFilter::forTenant(2)->get();

    expect($tenantAFilters)->toHaveCount(1)
        ->and($tenantAFilters->first()->name)->toBe('Tenant A Filter')
        ->and($tenantBFilters)->toHaveCount(1)
        ->and($tenantBFilters->first()->name)->toBe('Tenant B Filter');
});

it('does not expose a shared filter from tenant A to tenant B even when is_shared is true', function () {
    $collectionA = StudioCollection::factory()->forTenant(1)->create();

    $userA = User::forceCreate([
        'name' => 'User A',
        'email' => 'usera@example.com',
        'password' => bcrypt('password'),
    ]);

    $userB = User::forceCreate([
        'name' => 'User B',
        'email' => 'userb@example.com',
        'password' => bcrypt('password'),
    ]);

    StudioSavedFilter::create([
        'collection_id' => $collectionA->id,
        'created_by' => $userA->id,
        'tenant_id' => 1,
        'name' => 'Shared in Tenant A',
        'is_shared' => true,
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);

    // Tenant B should not see tenant A's shared filter
    $tenantBFilters = StudioSavedFilter::forTenant(2)->visibleTo($userB->id)->get();

    expect($tenantBFilters)->toHaveCount(0);

    // Tenant A users should see it
    $tenantAFilters = StudioSavedFilter::forTenant(1)->visibleTo($userB->id)->get();

    expect($tenantAFilters)->toHaveCount(1)
        ->and($tenantAFilters->first()->name)->toBe('Shared in Tenant A');
});

it('isolates private filters so only the creator sees them within the same tenant', function () {
    $collection = StudioCollection::factory()->forTenant(1)->create();

    $creator = User::forceCreate([
        'name' => 'Creator',
        'email' => 'creator@example.com',
        'password' => bcrypt('password'),
    ]);

    $colleague = User::forceCreate([
        'name' => 'Colleague',
        'email' => 'colleague@example.com',
        'password' => bcrypt('password'),
    ]);

    StudioSavedFilter::create([
        'collection_id' => $collection->id,
        'created_by' => $creator->id,
        'tenant_id' => 1,
        'name' => 'Private Filter',
        'is_shared' => false,
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);

    // Creator sees their own private filter
    $creatorFilters = StudioSavedFilter::forTenant(1)->visibleTo($creator->id)->get();

    expect($creatorFilters)->toHaveCount(1)
        ->and($creatorFilters->first()->name)->toBe('Private Filter');

    // Colleague in the same tenant does NOT see the private filter
    $colleagueFilters = StudioSavedFilter::forTenant(1)->visibleTo($colleague->id)->get();

    expect($colleagueFilters)->toHaveCount(0);
});

it('isolates collection-scoped filters so tenant A collection filters do not appear for tenant B collection with same slug', function () {
    $collectionA = StudioCollection::factory()->forTenant(1)->create(['slug' => 'articles']);
    $collectionB = StudioCollection::factory()->forTenant(2)->create(['slug' => 'articles']);

    $user = User::forceCreate([
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => bcrypt('password'),
    ]);

    StudioSavedFilter::create([
        'collection_id' => $collectionA->id,
        'created_by' => $user->id,
        'tenant_id' => 1,
        'name' => 'Tenant A Articles Filter',
        'is_shared' => true,
        'filter_tree' => ['logic' => 'and', 'rules' => [
            ['field' => 'status', 'operator' => 'eq', 'value' => 'published'],
        ]],
    ]);

    StudioSavedFilter::create([
        'collection_id' => $collectionB->id,
        'created_by' => $user->id,
        'tenant_id' => 2,
        'name' => 'Tenant B Articles Filter',
        'is_shared' => true,
        'filter_tree' => ['logic' => 'and', 'rules' => []],
    ]);

    // Query by tenant B's collection — should only return tenant B's filter
    $filtersForB = StudioSavedFilter::forTenant(2)
        ->forCollection($collectionB->id)
        ->get();

    expect($filtersForB)->toHaveCount(1)
        ->and($filtersForB->first()->name)->toBe('Tenant B Articles Filter');

    // Query by tenant A's collection — should only return tenant A's filter
    $filtersForA = StudioSavedFilter::forTenant(1)
        ->forCollection($collectionA->id)
        ->get();

    expect($filtersForA)->toHaveCount(1)
        ->and($filtersForA->first()->name)->toBe('Tenant A Articles Filter');

    // Cross-tenant: tenant B querying tenant A's collection_id should get nothing
    $crossTenant = StudioSavedFilter::forTenant(2)
        ->forCollection($collectionA->id)
        ->get();

    expect($crossTenant)->toHaveCount(0);
});
