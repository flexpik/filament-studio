<?php

use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Widgets\MetricWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create(['tenant_id' => 1, 'name' => 'orders', 'slug' => 'orders']);

    $this->priceField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'price',
        'field_type' => 'decimal',
        'eav_cast' => EavCast::Decimal,
    ]);

    foreach ([10.00, 20.00, 30.00] as $price) {
        $record = StudioRecord::factory()->create([
            'collection_id' => $this->collection->id,
            'tenant_id' => 1,
        ]);
        StudioValue::create([
            'record_id' => $record->id,
            'field_id' => $this->priceField->id,
            'val_decimal' => $price,
        ]);
    }
});

it('computes a count metric', function () {
    $panel = StudioPanel::factory()->create([
        'tenant_id' => 1,
        'placement' => PanelPlacement::Dashboard,
        'panel_type' => 'metric',
        'header_label' => 'Total Orders',
        'config' => [
            'collection_id' => $this->collection->id,
            'field' => 'price',
            'aggregate_function' => 'count',
        ],
    ]);

    $widget = new MetricWidget;
    $widget->mount($panel);
    $stats = $widget->getStats();

    expect($stats)->toHaveCount(1);
});

it('computes a sum metric', function () {
    $panel = StudioPanel::factory()->create([
        'tenant_id' => 1,
        'placement' => PanelPlacement::Dashboard,
        'panel_type' => 'metric',
        'header_label' => 'Total Revenue',
        'config' => [
            'collection_id' => $this->collection->id,
            'field' => 'price',
            'aggregate_function' => 'sum',
            'prefix' => '$',
            'decimal_precision' => 2,
        ],
    ]);

    $widget = new MetricWidget;
    $widget->mount($panel);
    $stats = $widget->getStats();

    expect($stats)->toHaveCount(1);
});
