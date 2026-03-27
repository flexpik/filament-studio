<?php

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\RangeFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(RangeFieldType::$key)->toBe('range');
    expect(RangeFieldType::$label)->toBe('Range Slider');
    expect(RangeFieldType::$icon)->toBe('heroicon-o-adjustments-horizontal');
    expect(RangeFieldType::$eavCast)->toBe(EavCast::Decimal);
    expect(RangeFieldType::$category)->toBe('numeric');
});

it('generates a TextInput range component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'rating',
        'field_type' => 'range',
        'settings' => ['min' => 0, 'max' => 100, 'step' => 1],
    ]);

    $type = new RangeFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TextInput::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'rating',
        'field_type' => 'range',
        'settings' => [],
    ]);

    $type = new RangeFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = RangeFieldType::settingsSchema();

    expect($schema)->toBeArray();
    expect($schema)->not->toBeEmpty();
});
