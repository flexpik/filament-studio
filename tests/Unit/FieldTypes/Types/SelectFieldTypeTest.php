<?php

use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\SelectFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(SelectFieldType::$key)->toBe('select');
    expect(SelectFieldType::$label)->toBe('Select');
    expect(SelectFieldType::$icon)->toBe('heroicon-o-chevron-up-down');
    expect(SelectFieldType::$eavCast)->toBe(EavCast::Text);
    expect(SelectFieldType::$category)->toBe('selection');
});

it('generates a Select component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'status',
        'field_type' => 'select',
        'settings' => ['options_source' => 'static'],
    ]);

    $type = new SelectFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Select::class);
});

it('applies searchable setting', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'category',
        'field_type' => 'select',
        'settings' => ['searchable' => true],
    ]);

    $type = new SelectFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Select::class);
});

it('applies preload setting', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'region',
        'field_type' => 'select',
        'settings' => ['preload' => true],
    ]);

    $type = new SelectFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Select::class);
});

it('generates a TextColumn with badge for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'status',
        'field_type' => 'select',
        'settings' => [],
    ]);

    $type = new SelectFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = SelectFieldType::settingsSchema();

    expect($schema)->toBeArray();
    expect($schema)->not->toBeEmpty();
});
