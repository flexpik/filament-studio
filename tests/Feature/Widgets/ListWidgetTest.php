<?php

use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Widgets\ListWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create(['tenant_id' => 1]);

    $this->nameField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => EavCast::Text,
    ]);

    foreach (['Alice', 'Bob', 'Charlie'] as $name) {
        $record = StudioRecord::factory()->create([
            'collection_id' => $this->collection->id,
            'tenant_id' => 1,
        ]);
        StudioValue::create([
            'record_id' => $record->id,
            'field_id' => $this->nameField->id,
            'val_text' => $name,
        ]);
    }
});

it('creates a list widget with table', function () {
    $panel = StudioPanel::factory()->create([
        'tenant_id' => 1,
        'placement' => PanelPlacement::Dashboard,
        'panel_type' => 'list',
        'config' => [
            'collection_id' => $this->collection->id,
            'display_template' => '{{name}}',
            'sort_field' => 'name',
            'sort_direction' => 'asc',
            'limit' => 10,
        ],
    ]);

    $widget = new ListWidget;
    $widget->mount($panel);

    expect($widget)->toBeInstanceOf(ListWidget::class);
});
