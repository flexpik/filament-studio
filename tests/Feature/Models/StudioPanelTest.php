<?php

use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a dashboard panel', function () {
    $dashboard = StudioDashboard::factory()->create();

    $panel = StudioPanel::factory()->create([
        'dashboard_id' => $dashboard->id,
        'placement' => PanelPlacement::Dashboard,
        'panel_type' => 'metric',
        'config' => ['collection' => 'orders', 'aggregate' => 'count'],
    ]);

    expect($panel->placement)->toBe(PanelPlacement::Dashboard)
        ->and($panel->panel_type)->toBe('metric')
        ->and($panel->config)->toBeArray()
        ->and($panel->config['collection'])->toBe('orders');
});

it('creates a collection header panel', function () {
    $collection = StudioCollection::factory()->create();

    $panel = StudioPanel::factory()->create([
        'placement' => PanelPlacement::CollectionHeader,
        'context_collection_id' => $collection->id,
        'dashboard_id' => null,
        'panel_type' => 'metric',
    ]);

    expect($panel->placement)->toBe(PanelPlacement::CollectionHeader)
        ->and($panel->context_collection_id)->toBe($collection->id)
        ->and($panel->dashboard_id)->toBeNull();
});

it('belongs to a dashboard', function () {
    $dashboard = StudioDashboard::factory()->create();
    $panel = StudioPanel::factory()->create(['dashboard_id' => $dashboard->id]);

    expect($panel->dashboard->id)->toBe($dashboard->id);
});

it('belongs to a context collection', function () {
    $collection = StudioCollection::factory()->create();
    $panel = StudioPanel::factory()->create([
        'context_collection_id' => $collection->id,
        'placement' => PanelPlacement::CollectionHeader,
        'dashboard_id' => null,
    ]);

    expect($panel->contextCollection->id)->toBe($collection->id);
});

it('casts config to array', function () {
    $panel = StudioPanel::factory()->create([
        'config' => ['key' => 'value'],
    ]);

    $panel->refresh();

    expect($panel->config)->toBeArray()->toBe(['key' => 'value']);
});

it('casts header_visible to boolean', function () {
    $panel = StudioPanel::factory()->create(['header_visible' => true]);

    expect($panel->header_visible)->toBeTrue();
});

it('scopes panels by placement and collection', function () {
    $collection = StudioCollection::factory()->create(['tenant_id' => 1]);

    StudioPanel::factory()->create([
        'tenant_id' => 1,
        'placement' => PanelPlacement::CollectionHeader,
        'context_collection_id' => $collection->id,
        'dashboard_id' => null,
    ]);
    StudioPanel::factory()->create([
        'tenant_id' => 1,
        'placement' => PanelPlacement::CollectionFooter,
        'context_collection_id' => $collection->id,
        'dashboard_id' => null,
    ]);
    StudioPanel::factory()->create([
        'tenant_id' => 1,
        'placement' => PanelPlacement::Dashboard,
        'dashboard_id' => StudioDashboard::factory()->create(['tenant_id' => 1])->id,
    ]);

    $headers = StudioPanel::query()
        ->forPlacement(PanelPlacement::CollectionHeader, $collection->id)
        ->where('tenant_id', 1)
        ->get();

    expect($headers)->toHaveCount(1);
});

it('deletes panels when dashboard is deleted', function () {
    $dashboard = StudioDashboard::factory()->create();
    StudioPanel::factory()->count(3)->create(['dashboard_id' => $dashboard->id]);

    expect(StudioPanel::where('dashboard_id', $dashboard->id)->count())->toBe(3);

    $dashboard->delete();

    expect(StudioPanel::where('dashboard_id', $dashboard->id)->count())->toBe(0);
});
