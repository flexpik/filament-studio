<?php

use Filament\Forms\Components\TimePicker;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\TimeFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(TimeFieldType::$key)->toBe('time');
    expect(TimeFieldType::$label)->toBe('Time');
    expect(TimeFieldType::$icon)->toBe('heroicon-o-clock');
    expect(TimeFieldType::$eavCast)->toBe(EavCast::Datetime);
    expect(TimeFieldType::$category)->toBe('datetime');
});

it('generates a TimePicker component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'start_time',
        'field_type' => 'time',
        'settings' => [],
    ]);

    $type = new TimeFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TimePicker::class);
});

it('applies seconds setting', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'duration',
        'field_type' => 'time',
        'settings' => ['seconds' => true],
    ]);

    $type = new TimeFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TimePicker::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'start_time',
        'field_type' => 'time',
        'settings' => [],
    ]);

    $type = new TimeFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = TimeFieldType::settingsSchema();

    expect($schema)->toBeArray();
});
