<?php

use Filament\Forms\Components\Checkbox;
use Filament\Tables\Columns\IconColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\CheckboxFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(CheckboxFieldType::$key)->toBe('checkbox');
    expect(CheckboxFieldType::$label)->toBe('Checkbox');
    expect(CheckboxFieldType::$icon)->toBe('heroicon-o-check');
    expect(CheckboxFieldType::$eavCast)->toBe(EavCast::Boolean);
    expect(CheckboxFieldType::$category)->toBe('boolean');
});

it('generates a Checkbox component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'agree_tos',
        'field_type' => 'checkbox',
        'settings' => [],
    ]);

    $type = new CheckboxFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Checkbox::class);
});

it('generates an IconColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'agree_tos',
        'field_type' => 'checkbox',
        'settings' => [],
    ]);

    $type = new CheckboxFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(IconColumn::class);
});

it('returns a settings schema', function () {
    $schema = CheckboxFieldType::settingsSchema();

    expect($schema)->toBeArray();
});
