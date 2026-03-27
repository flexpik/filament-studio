<?php

use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Illuminate\Database\QueryException;

it('can be created with factory', function () {
    $value = StudioValue::factory()->create();

    expect($value)->toBeInstanceOf(StudioValue::class);
});

it('belongs to a record', function () {
    $value = StudioValue::factory()->create();

    expect($value->record)->toBeInstanceOf(StudioRecord::class);
});

it('belongs to a field', function () {
    $value = StudioValue::factory()->create();

    expect($value->field)->toBeInstanceOf(StudioField::class);
});

it('stores text values', function () {
    $value = StudioValue::factory()->create(['val_text' => 'Hello World']);

    expect($value->val_text)->toBe('Hello World');
});

it('stores integer values', function () {
    $value = StudioValue::factory()->create(['val_integer' => 42]);

    expect($value->val_integer)->toBe(42);
});

it('stores decimal values', function () {
    $value = StudioValue::factory()->create(['val_decimal' => 99.99]);

    expect((float) $value->val_decimal)->toBe(99.99);
});

it('stores boolean values', function () {
    $value = StudioValue::factory()->create(['val_boolean' => true]);

    expect($value->val_boolean)->toBeTrue();
});

it('stores datetime values', function () {
    $dt = now()->startOfMinute();
    $value = StudioValue::factory()->create(['val_datetime' => $dt]);

    expect($value->val_datetime->equalTo($dt))->toBeTrue();
});

it('stores json values', function () {
    $data = ['a' => 1, 'b' => [2, 3]];
    $value = StudioValue::factory()->create(['val_json' => $data]);

    expect($value->val_json)->toBe($data);
});

it('resolves the correct value via resolveValue()', function () {
    $field = StudioField::factory()->create(['eav_cast' => 'integer']);
    $record = StudioRecord::factory()->create(['collection_id' => $field->collection_id]);
    $value = StudioValue::factory()->create([
        'record_id' => $record->id,
        'field_id' => $field->id,
        'val_integer' => 42,
    ]);

    expect($value->resolveValue())->toBe(42);
});

it('enforces unique record_id + field_id', function () {
    $value = StudioValue::factory()->create();

    StudioValue::factory()->create([
        'record_id' => $value->record_id,
        'field_id' => $value->field_id,
    ]);
})->throws(QueryException::class);
