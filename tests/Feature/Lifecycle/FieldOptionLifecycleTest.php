<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioFieldOption;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;

// -- 1. Update label --

it('persists a label change on a field option', function () {
    $option = StudioFieldOption::factory()->create(['label' => 'Original']);

    $option->update(['label' => 'Updated']);
    $option->refresh();

    expect($option->label)->toBe('Updated');
});

it('does not affect existing StudioValue records when option label changes', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'field_type' => 'select',
    ]);
    $option = StudioFieldOption::factory()->create([
        'field_id' => $field->id,
        'value' => 'opt-a',
        'label' => 'Option A',
    ]);
    $record = StudioRecord::factory()->create(['collection_id' => $collection->id]);
    $value = StudioValue::factory()->withText('opt-a')->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
    ]);

    $option->update(['label' => 'Renamed Option A']);

    $value->refresh();
    expect($value->val_text)->toBe('opt-a');
});

// -- 2. Update value (denormalized) --

it('persists a value change on a field option', function () {
    $option = StudioFieldOption::factory()->create(['value' => 'old-val']);

    $option->update(['value' => 'new-val']);
    $option->refresh();

    expect($option->value)->toBe('new-val');
});

it('does not retroactively update existing StudioValue records when option value changes', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'field_type' => 'select',
    ]);
    $option = StudioFieldOption::factory()->create([
        'field_id' => $field->id,
        'value' => 'old-val',
        'label' => 'Old',
    ]);
    $record = StudioRecord::factory()->create(['collection_id' => $collection->id]);
    $value = StudioValue::factory()->withText('old-val')->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
    ]);

    $option->update(['value' => 'new-val']);

    $value->refresh();
    expect($value->val_text)->toBe('old-val')
        ->and($option->fresh()->value)->toBe('new-val');
});

// -- 3. Delete option --

it('removes the option from the database when deleted', function () {
    $option = StudioFieldOption::factory()->create();
    $optionId = $option->id;

    $option->delete();

    expect(StudioFieldOption::find($optionId))->toBeNull();
});

it('leaves existing StudioValue records intact when an option is deleted (orphaned reference)', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'field_type' => 'select',
    ]);
    $option = StudioFieldOption::factory()->create([
        'field_id' => $field->id,
        'value' => 'doomed',
        'label' => 'Doomed Option',
    ]);
    $record = StudioRecord::factory()->create(['collection_id' => $collection->id]);
    $value = StudioValue::factory()->withText('doomed')->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
    ]);

    $option->delete();

    $value->refresh();
    expect($value->val_text)->toBe('doomed')
        ->and(StudioFieldOption::where('value', 'doomed')->where('field_id', $field->id)->exists())->toBeFalse();
});

// -- 4. Reorder --

it('reflects new sort_order via the ordered scope', function () {
    $field = StudioField::factory()->create(['field_type' => 'select']);

    $optionA = StudioFieldOption::factory()->create([
        'field_id' => $field->id,
        'value' => 'a',
        'label' => 'A',
        'sort_order' => 1,
    ]);
    $optionB = StudioFieldOption::factory()->create([
        'field_id' => $field->id,
        'value' => 'b',
        'label' => 'B',
        'sort_order' => 2,
    ]);
    $optionC = StudioFieldOption::factory()->create([
        'field_id' => $field->id,
        'value' => 'c',
        'label' => 'C',
        'sort_order' => 3,
    ]);

    // Reorder: C first, A second, B third
    $optionC->update(['sort_order' => 1]);
    $optionA->update(['sort_order' => 2]);
    $optionB->update(['sort_order' => 3]);

    $ordered = StudioFieldOption::where('field_id', $field->id)->ordered()->pluck('value')->all();

    expect($ordered)->toBe(['c', 'a', 'b']);
});

it('handles tied sort_order values gracefully', function () {
    $field = StudioField::factory()->create(['field_type' => 'select']);

    StudioFieldOption::factory()->create([
        'field_id' => $field->id,
        'value' => 'x',
        'sort_order' => 0,
    ]);
    StudioFieldOption::factory()->create([
        'field_id' => $field->id,
        'value' => 'y',
        'sort_order' => 0,
    ]);

    $ordered = StudioFieldOption::where('field_id', $field->id)->ordered()->get();

    expect($ordered)->toHaveCount(2);
});
