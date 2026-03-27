<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioPanel;

it('isolates dashboards so tenant A dashboards are not visible to tenant B via forTenant scope', function () {
    StudioDashboard::factory()->create(['tenant_id' => 1, 'name' => 'Sales Dashboard', 'slug' => 'sales']);
    StudioDashboard::factory()->create(['tenant_id' => 1, 'name' => 'Marketing Dashboard', 'slug' => 'marketing']);
    StudioDashboard::factory()->create(['tenant_id' => 2, 'name' => 'HR Dashboard', 'slug' => 'hr']);

    $tenant1Dashboards = StudioDashboard::query()->forTenant(1)->get();
    $tenant2Dashboards = StudioDashboard::query()->forTenant(2)->get();

    expect($tenant1Dashboards)->toHaveCount(2)
        ->and($tenant1Dashboards->pluck('slug')->toArray())->toEqualCanonicalizing(['sales', 'marketing'])
        ->and($tenant2Dashboards)->toHaveCount(1)
        ->and($tenant2Dashboards->first()->slug)->toBe('hr');
});

it('isolates panels so tenant A panels are not visible to tenant B via forTenant scope', function () {
    $dashboardA = StudioDashboard::factory()->create(['tenant_id' => 1, 'slug' => 'dash-a']);
    $dashboardB = StudioDashboard::factory()->create(['tenant_id' => 2, 'slug' => 'dash-b']);

    StudioPanel::factory()->forDashboard($dashboardA)->count(3)->create();
    StudioPanel::factory()->forDashboard($dashboardB)->count(2)->create();

    $tenant1Panels = StudioPanel::query()->forTenant(1)->get();
    $tenant2Panels = StudioPanel::query()->forTenant(2)->get();

    expect($tenant1Panels)->toHaveCount(3)
        ->and($tenant1Panels->pluck('tenant_id')->unique()->toArray())->toBe([1])
        ->and($tenant2Panels)->toHaveCount(2)
        ->and($tenant2Panels->pluck('tenant_id')->unique()->toArray())->toBe([2]);
});

it('isolates dashboards with panels so creating for tenant A returns nothing when querying tenant B', function () {
    $dashboardA = StudioDashboard::factory()->create(['tenant_id' => 1, 'name' => 'Ops', 'slug' => 'ops']);
    StudioPanel::factory()->forDashboard($dashboardA)->count(2)->create();

    $tenant2Dashboards = StudioDashboard::query()->forTenant(2)->get();
    $tenant2Panels = StudioPanel::query()->forTenant(2)->get();

    expect($tenant2Dashboards)->toHaveCount(0)
        ->and($tenant2Panels)->toHaveCount(0);

    // Verify tenant A sees everything
    $tenant1Dashboards = StudioDashboard::query()->forTenant(1)->get();
    $tenant1Panels = StudioPanel::query()->forTenant(1)->get();

    expect($tenant1Dashboards)->toHaveCount(1)
        ->and($tenant1Dashboards->first()->slug)->toBe('ops')
        ->and($tenant1Panels)->toHaveCount(2);
});

it('allows two tenants to have dashboards with the same slug without collision', function () {
    $dashboardA = StudioDashboard::factory()->create(['tenant_id' => 1, 'name' => 'Analytics', 'slug' => 'analytics']);
    $dashboardB = StudioDashboard::factory()->create(['tenant_id' => 2, 'name' => 'Analytics', 'slug' => 'analytics']);

    $tenant1 = StudioDashboard::query()->forTenant(1)->where('slug', 'analytics')->first();
    $tenant2 = StudioDashboard::query()->forTenant(2)->where('slug', 'analytics')->first();

    expect($tenant1)->not->toBeNull()
        ->and($tenant2)->not->toBeNull()
        ->and($tenant1->id)->not->toBe($tenant2->id)
        ->and($tenant1->tenant_id)->toBe(1)
        ->and($tenant2->tenant_id)->toBe(2);

    // Verify each tenant sees exactly one dashboard with that slug
    $tenant1Count = StudioDashboard::query()->forTenant(1)->where('slug', 'analytics')->count();
    $tenant2Count = StudioDashboard::query()->forTenant(2)->where('slug', 'analytics')->count();

    expect($tenant1Count)->toBe(1)
        ->and($tenant2Count)->toBe(1);
});

it('isolates panels attached to a collection context so tenant B cannot see tenant A panels', function () {
    $collectionA = StudioCollection::factory()->forTenant(1)->create(['slug' => 'orders']);
    $collectionB = StudioCollection::factory()->forTenant(2)->create(['slug' => 'orders']);

    StudioPanel::factory()->forCollectionHeader($collectionA->id)->create([
        'tenant_id' => 1,
        'header_label' => 'Order Stats',
    ]);
    StudioPanel::factory()->forCollectionHeader($collectionA->id)->create([
        'tenant_id' => 1,
        'header_label' => 'Revenue Widget',
    ]);

    // Tenant B queries panels for their own collection — should see none
    $tenant2Panels = StudioPanel::query()
        ->forTenant(2)
        ->where('context_collection_id', $collectionB->id)
        ->get();

    expect($tenant2Panels)->toHaveCount(0);

    // Tenant B queries panels scoped to tenant A's collection — still none via forTenant
    $crossTenantPanels = StudioPanel::query()
        ->forTenant(2)
        ->where('context_collection_id', $collectionA->id)
        ->get();

    expect($crossTenantPanels)->toHaveCount(0);

    // Tenant A can see their own panels
    $tenant1Panels = StudioPanel::query()
        ->forTenant(1)
        ->where('context_collection_id', $collectionA->id)
        ->get();

    expect($tenant1Panels)->toHaveCount(2)
        ->and($tenant1Panels->pluck('header_label')->toArray())->toEqualCanonicalizing(['Order Stats', 'Revenue Widget']);
});
