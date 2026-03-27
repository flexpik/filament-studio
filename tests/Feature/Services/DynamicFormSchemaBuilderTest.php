<?php

use Filament\Schemas\Components\Section;
use Flexpik\FilamentStudio\FieldTypes\FieldTypeRegistry;
use Flexpik\FilamentStudio\FieldTypes\Types\SectionHeaderFieldType;
use Flexpik\FilamentStudio\FieldTypes\Types\TextFieldType;
use Flexpik\FilamentStudio\FieldTypes\Types\ToggleFieldType;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Services\DynamicFormSchemaBuilder;

beforeEach(function () {
    $registry = app(FieldTypeRegistry::class);
    $registry->register(TextFieldType::class);
    $registry->register(ToggleFieldType::class);
    $registry->register(SectionHeaderFieldType::class);
});

it('builds a flat schema when no section headers exist', function () {
    $collection = StudioCollection::factory()->create();

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'sort_order' => 1,
        'settings' => [],
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'is_active',
        'field_type' => 'toggle',
        'sort_order' => 2,
        'settings' => [],
    ]);

    $schema = DynamicFormSchemaBuilder::build($collection);

    expect($schema)->toBeArray();
    expect($schema)->toHaveCount(1); // One implicit section
    expect($schema[0])->toBeInstanceOf(Section::class);
});

it('groups fields into sections based on section_header fields', function () {
    $collection = StudioCollection::factory()->create();

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'basic_section',
        'field_type' => 'section_header',
        'sort_order' => 1,
        'settings' => ['section_label' => 'Basic Info', 'columns' => 2],
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'name',
        'field_type' => 'text',
        'sort_order' => 2,
        'settings' => [],
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'details_section',
        'field_type' => 'section_header',
        'sort_order' => 3,
        'settings' => ['section_label' => 'Details', 'collapsible' => true],
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'is_published',
        'field_type' => 'toggle',
        'sort_order' => 4,
        'settings' => [],
    ]);

    $schema = DynamicFormSchemaBuilder::build($collection);

    expect($schema)->toBeArray();
    expect($schema)->toHaveCount(2);
    expect($schema[0])->toBeInstanceOf(Section::class);
    expect($schema[1])->toBeInstanceOf(Section::class);
});

it('excludes fields hidden in form', function () {
    $collection = StudioCollection::factory()->create();

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'sort_order' => 1,
        'is_hidden_in_form' => false,
        'settings' => [],
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'secret_notes',
        'field_type' => 'text',
        'sort_order' => 2,
        'is_hidden_in_form' => true,
        'settings' => [],
    ]);

    $schema = DynamicFormSchemaBuilder::build($collection);

    // One implicit section with only one visible field
    expect($schema)->toHaveCount(1);
});

it('returns empty array for collection with no fields', function () {
    $collection = StudioCollection::factory()->create();

    $schema = DynamicFormSchemaBuilder::build($collection);

    expect($schema)->toBeArray();
    expect($schema)->toBeEmpty();
});
