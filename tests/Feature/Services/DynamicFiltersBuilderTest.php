<?php

use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\FieldTypes\FieldTypeRegistry;
use Flexpik\FilamentStudio\FieldTypes\Types\SectionHeaderFieldType;
use Flexpik\FilamentStudio\FieldTypes\Types\SelectFieldType;
use Flexpik\FilamentStudio\FieldTypes\Types\TextFieldType;
use Flexpik\FilamentStudio\FieldTypes\Types\ToggleFieldType;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Services\DynamicFiltersBuilder;

beforeEach(function () {
    $registry = app(FieldTypeRegistry::class);
    $registry->register(TextFieldType::class);
    $registry->register(ToggleFieldType::class);
    $registry->register(SelectFieldType::class);
    $registry->register(SectionHeaderFieldType::class);
});

it('builds filters from collection fields', function () {
    $collection = StudioCollection::factory()->create();

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'name',
        'field_type' => 'text',
        'sort_order' => 1,
        'is_filterable' => true,
        'settings' => [],
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'is_active',
        'field_type' => 'toggle',
        'sort_order' => 2,
        'is_filterable' => true,
        'settings' => [],
    ]);

    $filters = DynamicFiltersBuilder::build($collection);

    expect($filters)->toBeArray();
    expect($filters)->toHaveCount(2);
    expect($filters[0])->toBeInstanceOf(BaseFilter::class);
    expect($filters[1])->toBeInstanceOf(BaseFilter::class);
});

it('excludes non-filterable fields', function () {
    $collection = StudioCollection::factory()->create();

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'name',
        'field_type' => 'text',
        'sort_order' => 1,
        'is_filterable' => true,
        'settings' => [],
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'notes',
        'field_type' => 'text',
        'sort_order' => 2,
        'is_filterable' => false,
        'settings' => [],
    ]);

    $filters = DynamicFiltersBuilder::build($collection);

    expect($filters)->toHaveCount(1);
});

it('excludes fields whose toFilter returns null', function () {
    $collection = StudioCollection::factory()->create();

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'section_1',
        'field_type' => 'section_header',
        'sort_order' => 1,
        'is_filterable' => true,
        'settings' => [],
    ]);

    $filters = DynamicFiltersBuilder::build($collection);

    expect($filters)->toHaveCount(0);
});

it('returns empty array for collection with no filterable fields', function () {
    $collection = StudioCollection::factory()->create();

    $filters = DynamicFiltersBuilder::build($collection);

    expect($filters)->toBeArray();
    expect($filters)->toBeEmpty();
});
