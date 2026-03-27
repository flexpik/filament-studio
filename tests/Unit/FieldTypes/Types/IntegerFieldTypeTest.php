<?php

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\IntegerFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(IntegerFieldType::$key)->toBe('integer');
    expect(IntegerFieldType::$label)->toBe('Integer');
    expect(IntegerFieldType::$icon)->toBe('heroicon-o-calculator');
    expect(IntegerFieldType::$eavCast)->toBe(EavCast::Integer);
    expect(IntegerFieldType::$category)->toBe('numeric');
});

it('generates a numeric TextInput component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'quantity',
        'field_type' => 'integer',
        'settings' => [],
    ]);

    $type = new IntegerFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TextInput::class);
});

it('applies min, max, step settings', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'age',
        'field_type' => 'integer',
        'settings' => ['min' => 0, 'max' => 150, 'step' => 1],
    ]);

    $type = new IntegerFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TextInput::class);
});

it('applies prefix and suffix', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'count',
        'field_type' => 'integer',
        'settings' => ['prefix' => '#', 'suffix' => 'items'],
    ]);

    $type = new IntegerFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TextInput::class);
});

it('generates a numeric TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'quantity',
        'field_type' => 'integer',
        'settings' => [],
    ]);

    $type = new IntegerFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = IntegerFieldType::settingsSchema();

    expect($schema)->toBeArray();
    expect($schema)->not->toBeEmpty();
});
