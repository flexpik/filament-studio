<?php

use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\MultiSelectFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(MultiSelectFieldType::$key)->toBe('multi_select');
    expect(MultiSelectFieldType::$label)->toBe('Multi Select');
    expect(MultiSelectFieldType::$icon)->toBe('heroicon-o-list-bullet');
    expect(MultiSelectFieldType::$eavCast)->toBe(EavCast::Json);
    expect(MultiSelectFieldType::$category)->toBe('selection');
});

it('generates a multiple Select component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'tags',
        'field_type' => 'multi_select',
        'settings' => [],
    ]);

    $type = new MultiSelectFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Select::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'tags',
        'field_type' => 'multi_select',
        'settings' => [],
    ]);

    $type = new MultiSelectFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = MultiSelectFieldType::settingsSchema();

    expect($schema)->toBeArray();
    expect($schema)->not->toBeEmpty();
});
