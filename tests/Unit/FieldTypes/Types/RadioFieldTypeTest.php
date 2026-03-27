<?php

use Filament\Forms\Components\Radio;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\RadioFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(RadioFieldType::$key)->toBe('radio');
    expect(RadioFieldType::$label)->toBe('Radio');
    expect(RadioFieldType::$icon)->toBe('heroicon-o-stop-circle');
    expect(RadioFieldType::$eavCast)->toBe(EavCast::Text);
    expect(RadioFieldType::$category)->toBe('selection');
});

it('generates a Radio component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'priority',
        'field_type' => 'radio',
        'settings' => [],
    ]);

    $type = new RadioFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Radio::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'priority',
        'field_type' => 'radio',
        'settings' => [],
    ]);

    $type = new RadioFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = RadioFieldType::settingsSchema();

    expect($schema)->toBeArray();
});
