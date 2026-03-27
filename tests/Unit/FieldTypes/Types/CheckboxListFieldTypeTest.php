<?php

use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\CheckboxListFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(CheckboxListFieldType::$key)->toBe('checkbox_list');
    expect(CheckboxListFieldType::$label)->toBe('Checkbox List');
    expect(CheckboxListFieldType::$icon)->toBe('heroicon-o-queue-list');
    expect(CheckboxListFieldType::$eavCast)->toBe(EavCast::Json);
    expect(CheckboxListFieldType::$category)->toBe('selection');
});

it('generates a CheckboxList component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'features',
        'field_type' => 'checkbox_list',
        'settings' => [],
    ]);

    $type = new CheckboxListFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(CheckboxList::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'features',
        'field_type' => 'checkbox_list',
        'settings' => [],
    ]);

    $type = new CheckboxListFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = CheckboxListFieldType::settingsSchema();

    expect($schema)->toBeArray();
});
