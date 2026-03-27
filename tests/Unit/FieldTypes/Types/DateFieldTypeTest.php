<?php

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\DateFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(DateFieldType::$key)->toBe('date');
    expect(DateFieldType::$label)->toBe('Date');
    expect(DateFieldType::$icon)->toBe('heroicon-o-calendar');
    expect(DateFieldType::$eavCast)->toBe(EavCast::Datetime);
    expect(DateFieldType::$category)->toBe('datetime');
});

it('generates a DatePicker component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'birth_date',
        'field_type' => 'date',
        'settings' => [],
    ]);

    $type = new DateFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(DatePicker::class);
});

it('applies display format setting', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'event_date',
        'field_type' => 'date',
        'settings' => ['display_format' => 'd/m/Y'],
    ]);

    $type = new DateFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(DatePicker::class);
});

it('applies close_on_select setting', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'due_date',
        'field_type' => 'date',
        'settings' => ['close_on_select' => true],
    ]);

    $type = new DateFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(DatePicker::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'birth_date',
        'field_type' => 'date',
        'settings' => [],
    ]);

    $type = new DateFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = DateFieldType::settingsSchema();

    expect($schema)->toBeArray();
    expect($schema)->not->toBeEmpty();
});
