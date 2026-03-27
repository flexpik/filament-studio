<?php

use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('finds header panels for a collection', function () {
    $collection = StudioCollection::factory()->create(['tenant_id' => 1]);

    StudioPanel::factory()->create([
        'tenant_id' => 1,
        'placement' => PanelPlacement::CollectionHeader,
        'context_collection_id' => $collection->id,
        'dashboard_id' => null,
        'panel_type' => 'metric',
        'config' => ['collection_id' => $collection->id, 'field' => 'id', 'aggregate_function' => 'count'],
    ]);

    $panels = StudioPanel::query()
        ->forPlacement(PanelPlacement::CollectionHeader, $collection->id)
        ->where('tenant_id', 1)
        ->get();

    expect($panels)->toHaveCount(1);
});

it('finds footer panels for a collection', function () {
    $collection = StudioCollection::factory()->create(['tenant_id' => 1]);

    StudioPanel::factory()->create([
        'tenant_id' => 1,
        'placement' => PanelPlacement::CollectionFooter,
        'context_collection_id' => $collection->id,
        'dashboard_id' => null,
        'panel_type' => 'label',
        'config' => ['text' => 'Footer'],
    ]);

    $panels = StudioPanel::query()
        ->forPlacement(PanelPlacement::CollectionFooter, $collection->id)
        ->where('tenant_id', 1)
        ->get();

    expect($panels)->toHaveCount(1);
});

it('finds record header panels for a collection', function () {
    $collection = StudioCollection::factory()->create(['tenant_id' => 1]);

    StudioPanel::factory()->create([
        'tenant_id' => 1,
        'placement' => PanelPlacement::RecordHeader,
        'context_collection_id' => $collection->id,
        'dashboard_id' => null,
        'panel_type' => 'metric',
        'config' => ['collection_id' => $collection->id, 'field' => 'id', 'aggregate_function' => 'count'],
    ]);

    $panels = StudioPanel::query()
        ->forPlacement(PanelPlacement::RecordHeader, $collection->id)
        ->where('tenant_id', 1)
        ->get();

    expect($panels)->toHaveCount(1);
});
