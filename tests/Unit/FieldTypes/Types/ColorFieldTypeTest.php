<?php

use Filament\Forms\Components\ColorPicker;
use Filament\Tables\Columns\ColorColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\ColorFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(ColorFieldType::$key)->toBe('color');
    expect(ColorFieldType::$label)->toBe('Color Picker');
    expect(ColorFieldType::$icon)->toBe('heroicon-o-swatch');
    expect(ColorFieldType::$eavCast)->toBe(EavCast::Text);
    expect(ColorFieldType::$category)->toBe('structured');
});

it('generates a ColorPicker component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'brand_color',
        'field_type' => 'color',
        'settings' => [],
    ]);

    $type = new ColorFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(ColorPicker::class);
});

it('generates a ColorColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'brand_color',
        'field_type' => 'color',
        'settings' => [],
    ]);

    $type = new ColorFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(ColorColumn::class);
});

it('returns a settings schema', function () {
    $schema = ColorFieldType::settingsSchema();

    expect($schema)->toBeArray();
});
