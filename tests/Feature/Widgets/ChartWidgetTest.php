<?php

use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Widgets\TimeSeriesWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create(['tenant_id' => 1, 'name' => 'sales', 'slug' => 'sales']);

    $this->dateField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'sale_date',
        'label' => 'Sale Date',
        'field_type' => 'datetime',
        'eav_cast' => EavCast::Datetime,
    ]);

    $this->amountField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'amount',
        'label' => 'Amount',
        'field_type' => 'decimal',
        'eav_cast' => EavCast::Decimal,
    ]);

    foreach ([50.00, 75.00, 100.00] as $index => $amount) {
        $record = StudioRecord::factory()->create([
            'collection_id' => $this->collection->id,
            'tenant_id' => 1,
        ]);
        StudioValue::create([
            'record_id' => $record->id,
            'field_id' => $this->dateField->id,
            'val_datetime' => now()->subDays($index)->format('Y-m-d H:i:s'),
        ]);
        StudioValue::create([
            'record_id' => $record->id,
            'field_id' => $this->amountField->id,
            'val_decimal' => $amount,
        ]);
    }
});

it('returns line as the chart type', function () {
    $panel = StudioPanel::factory()->create([
        'tenant_id' => 1,
        'placement' => PanelPlacement::Dashboard,
        'panel_type' => 'time_series',
        'header_label' => 'Sales Over Time',
        'config' => [
            'collection_id' => $this->collection->id,
            'date_field' => 'sale_date',
            'value_field' => 'amount',
            'aggregate_function' => 'sum',
            'group_precision' => 'day',
        ],
    ]);

    $widget = new TimeSeriesWidget;
    $widget->mount($panel);

    expect($widget->getData())->toHaveKeys(['labels', 'datasets']);
});

it('produces data with labels and datasets', function () {
    $panel = StudioPanel::factory()->create([
        'tenant_id' => 1,
        'placement' => PanelPlacement::Dashboard,
        'panel_type' => 'time_series',
        'header_label' => 'Daily Revenue',
        'config' => [
            'collection_id' => $this->collection->id,
            'date_field' => 'sale_date',
            'value_field' => 'amount',
            'aggregate_function' => 'sum',
            'group_precision' => 'day',
        ],
    ]);

    $widget = new TimeSeriesWidget;
    $widget->mount($panel);

    $data = $widget->getData();

    expect($data)->toHaveKeys(['labels', 'datasets'])
        ->and($data['labels'])->toBeArray()
        ->and($data['datasets'])->toBeArray()
        ->and($data['datasets'])->not->toBeEmpty()
        ->and($data['datasets'][0])->toHaveKey('data');
});
