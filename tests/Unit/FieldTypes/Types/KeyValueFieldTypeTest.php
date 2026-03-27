<?php

use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\KeyValueFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(KeyValueFieldType::$key)->toBe('key_value');
    expect(KeyValueFieldType::$label)->toBe('Key-Value');
    expect(KeyValueFieldType::$icon)->toBe('heroicon-o-table-cells');
    expect(KeyValueFieldType::$eavCast)->toBe(EavCast::Json);
    expect(KeyValueFieldType::$category)->toBe('structured');
});

it('generates a KeyValue component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'metadata',
        'field_type' => 'key_value',
        'settings' => [],
    ]);

    $type = new KeyValueFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(KeyValue::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'metadata',
        'field_type' => 'key_value',
        'settings' => [],
    ]);

    $type = new KeyValueFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = KeyValueFieldType::settingsSchema();

    expect($schema)->toBeArray();
});
