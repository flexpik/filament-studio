<?php

use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioFieldOption;

it('can be created with factory', function () {
    $option = StudioFieldOption::factory()->create();

    expect($option)->toBeInstanceOf(StudioFieldOption::class);
    expect($option->value)->not->toBeEmpty();
    expect($option->label)->not->toBeEmpty();
});

it('belongs to a field', function () {
    $field = StudioField::factory()->create(['field_type' => 'select']);
    $option = StudioFieldOption::factory()->create(['field_id' => $field->id]);

    expect($option->field->id)->toBe($field->id);
});

it('scopes ordered by sort_order', function () {
    $field = StudioField::factory()->create(['field_type' => 'select']);
    StudioFieldOption::factory()->create(['field_id' => $field->id, 'sort_order' => 2, 'value' => 'b']);
    StudioFieldOption::factory()->create(['field_id' => $field->id, 'sort_order' => 1, 'value' => 'a']);

    $ordered = StudioFieldOption::ordered()->get();
    expect($ordered->first()->value)->toBe('a');
});
