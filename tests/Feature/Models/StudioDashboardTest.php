<?php

use Flexpik\FilamentStudio\Models\StudioDashboard;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a dashboard with required fields', function () {
    $dashboard = StudioDashboard::factory()->create([
        'name' => 'Sales Overview',
        'slug' => 'sales-overview',
        'tenant_id' => 1,
    ]);

    expect($dashboard)->toBeInstanceOf(StudioDashboard::class)
        ->and($dashboard->name)->toBe('Sales Overview')
        ->and($dashboard->slug)->toBe('sales-overview')
        ->and($dashboard->tenant_id)->toBe(1);
});

it('enforces unique slug per tenant', function () {
    StudioDashboard::factory()->create([
        'tenant_id' => 1,
        'slug' => 'sales',
    ]);

    StudioDashboard::factory()->create([
        'tenant_id' => 1,
        'slug' => 'sales',
    ]);
})->throws(QueryException::class);

it('allows same slug across different tenants', function () {
    $d1 = StudioDashboard::factory()->create(['tenant_id' => 1, 'slug' => 'sales']);
    $d2 = StudioDashboard::factory()->create(['tenant_id' => 2, 'slug' => 'sales']);

    expect($d1->slug)->toBe($d2->slug);
});

it('has many panels relationship', function () {
    $dashboard = StudioDashboard::factory()->create();

    expect($dashboard->panels())->toBeInstanceOf(HasMany::class);
});

it('scopes by tenant', function () {
    StudioDashboard::factory()->create(['tenant_id' => 1]);
    StudioDashboard::factory()->create(['tenant_id' => 2]);

    $results = StudioDashboard::query()->forTenant(1)->get();

    expect($results)->toHaveCount(1);
});

it('orders by sort_order', function () {
    StudioDashboard::factory()->create(['tenant_id' => 1, 'sort_order' => 3, 'name' => 'C']);
    StudioDashboard::factory()->create(['tenant_id' => 1, 'sort_order' => 1, 'name' => 'A']);
    StudioDashboard::factory()->create(['tenant_id' => 1, 'sort_order' => 2, 'name' => 'B']);

    $results = StudioDashboard::query()->forTenant(1)->ordered()->pluck('name')->toArray();

    expect($results)->toBe(['A', 'B', 'C']);
});

it('casts auto_refresh_interval to integer', function () {
    $dashboard = StudioDashboard::factory()->create(['auto_refresh_interval' => 30]);

    expect($dashboard->auto_refresh_interval)->toBeInt()->toBe(30);
});
