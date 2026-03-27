<?php

use Filament\Tables\Columns\Column;
use Flexpik\FilamentStudio\FieldTypes\FieldTypeRegistry;
use Flexpik\FilamentStudio\FieldTypes\Types\PasswordFieldType;
use Flexpik\FilamentStudio\FieldTypes\Types\SectionHeaderFieldType;
use Flexpik\FilamentStudio\FieldTypes\Types\TextFieldType;
use Flexpik\FilamentStudio\FieldTypes\Types\ToggleFieldType;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Services\DynamicTableColumnsBuilder;

beforeEach(function () {
    $registry = app(FieldTypeRegistry::class);
    $registry->register(TextFieldType::class);
    $registry->register(ToggleFieldType::class);
    $registry->register(SectionHeaderFieldType::class);
    $registry->register(PasswordFieldType::class);
});

it('builds table columns from collection fields', function () {
    $collection = StudioCollection::factory()->create();

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'sort_order' => 1,
        'is_hidden_in_table' => false,
        'settings' => [],
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'is_active',
        'field_type' => 'toggle',
        'sort_order' => 2,
        'is_hidden_in_table' => false,
        'settings' => [],
    ]);

    $columns = DynamicTableColumnsBuilder::build($collection);

    expect($columns)->toBeArray();
    expect($columns)->toHaveCount(2);
    expect($columns[0])->toBeInstanceOf(Column::class);
    expect($columns[1])->toBeInstanceOf(Column::class);
});

it('excludes fields hidden in table', function () {
    $collection = StudioCollection::factory()->create();

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'sort_order' => 1,
        'is_hidden_in_table' => false,
        'settings' => [],
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'notes',
        'field_type' => 'text',
        'sort_order' => 2,
        'is_hidden_in_table' => true,
        'settings' => [],
    ]);

    $columns = DynamicTableColumnsBuilder::build($collection);

    expect($columns)->toHaveCount(1);
});

it('excludes presentation field types that return null columns', function () {
    $collection = StudioCollection::factory()->create();

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'section_1',
        'field_type' => 'section_header',
        'sort_order' => 1,
        'is_hidden_in_table' => false,
        'settings' => [],
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'sort_order' => 2,
        'is_hidden_in_table' => false,
        'settings' => [],
    ]);

    $columns = DynamicTableColumnsBuilder::build($collection);

    expect($columns)->toHaveCount(1);
});

it('excludes password fields that return null columns', function () {
    $collection = StudioCollection::factory()->create();

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'secret',
        'field_type' => 'password',
        'sort_order' => 1,
        'is_hidden_in_table' => false,
        'settings' => [],
    ]);

    $columns = DynamicTableColumnsBuilder::build($collection);

    expect($columns)->toHaveCount(0);
});

it('returns empty array for collection with no visible fields', function () {
    $collection = StudioCollection::factory()->create();

    $columns = DynamicTableColumnsBuilder::build($collection);

    expect($columns)->toBeArray();
    expect($columns)->toBeEmpty();
});
