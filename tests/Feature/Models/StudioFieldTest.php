<?php

use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Enums\FieldWidth;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioFieldOption;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;

it('can be created with factory', function () {
    $field = StudioField::factory()->create();

    expect($field)
        ->toBeInstanceOf(StudioField::class)
        ->column_name->not->toBeEmpty()
        ->label->not->toBeEmpty()
        ->field_type->not->toBeEmpty();
});

it('casts attributes correctly', function () {
    $field = StudioField::factory()->create([
        'eav_cast' => 'integer',
        'width' => 'half',
        'is_required' => true,
        'is_unique' => false,
        'is_system' => true,
        'validation_rules' => ['min:3', 'max:100'],
        'settings' => ['subtype' => 'email'],
        'auto_fill_on' => ['create'],
    ]);

    expect($field->eav_cast)->toBe(EavCast::Integer)
        ->and($field->width)->toBe(FieldWidth::Half)
        ->and($field->is_required)->toBeTrue()
        ->and($field->is_unique)->toBeFalse()
        ->and($field->is_system)->toBeTrue()
        ->and($field->validation_rules)->toBe(['min:3', 'max:100'])
        ->and($field->settings)->toBe(['subtype' => 'email'])
        ->and($field->auto_fill_on)->toBe(['create']);
});

it('belongs to a collection', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create(['collection_id' => $collection->id]);

    expect($field->collection->id)->toBe($collection->id);
});

it('has options relationship', function () {
    $field = StudioField::factory()->create(['field_type' => 'select']);
    StudioFieldOption::factory()->create(['field_id' => $field->id]);

    expect($field->options)->toHaveCount(1);
});

it('has values relationship', function () {
    $field = StudioField::factory()->create();

    expect($field->values())->toBeInstanceOf(HasMany::class);
});

it('scopes ordered by sort_order', function () {
    $collection = StudioCollection::factory()->create();
    StudioField::factory()->create(['collection_id' => $collection->id, 'sort_order' => 2, 'column_name' => 'b']);
    StudioField::factory()->create(['collection_id' => $collection->id, 'sort_order' => 1, 'column_name' => 'a']);

    $ordered = StudioField::ordered()->get();

    expect($ordered->first()->column_name)->toBe('a');
});

it('knows its eav column name', function () {
    $field = StudioField::factory()->create(['eav_cast' => 'decimal']);

    expect($field->eavColumn())->toBe('val_decimal');
});

it('enforces unique column_name per collection', function () {
    $collection = StudioCollection::factory()->create();
    StudioField::factory()->create(['collection_id' => $collection->id, 'column_name' => 'title']);

    StudioField::factory()->create(['collection_id' => $collection->id, 'column_name' => 'title']);
})->throws(QueryException::class);
