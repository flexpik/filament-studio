<?php

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\DecimalFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(DecimalFieldType::$key)->toBe('decimal');
    expect(DecimalFieldType::$label)->toBe('Decimal');
    expect(DecimalFieldType::$icon)->toBe('heroicon-o-variable');
    expect(DecimalFieldType::$eavCast)->toBe(EavCast::Decimal);
    expect(DecimalFieldType::$category)->toBe('numeric');
});

it('generates a numeric TextInput component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'price',
        'field_type' => 'decimal',
        'settings' => [],
    ]);

    $type = new DecimalFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TextInput::class);
});

it('applies precision and scale settings', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'amount',
        'field_type' => 'decimal',
        'settings' => ['precision' => 10, 'scale' => 2],
    ]);

    $type = new DecimalFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TextInput::class);
});

it('generates a numeric TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'price',
        'field_type' => 'decimal',
        'settings' => ['scale' => 2],
    ]);

    $type = new DecimalFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = DecimalFieldType::settingsSchema();

    expect($schema)->toBeArray();
    expect($schema)->not->toBeEmpty();
});
