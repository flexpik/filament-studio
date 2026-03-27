<?php

use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\DatetimeFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(DatetimeFieldType::$key)->toBe('datetime');
    expect(DatetimeFieldType::$label)->toBe('Date & Time');
    expect(DatetimeFieldType::$icon)->toBe('heroicon-o-calendar-days');
    expect(DatetimeFieldType::$eavCast)->toBe(EavCast::Datetime);
    expect(DatetimeFieldType::$category)->toBe('datetime');
});

it('generates a DateTimePicker component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'published_at',
        'field_type' => 'datetime',
        'settings' => [],
    ]);

    $type = new DatetimeFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(DateTimePicker::class);
});

it('applies display format setting', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'event_at',
        'field_type' => 'datetime',
        'settings' => ['display_format' => 'Y-m-d H:i'],
    ]);

    $type = new DatetimeFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(DateTimePicker::class);
});

it('applies timezone setting', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'scheduled_at',
        'field_type' => 'datetime',
        'settings' => ['timezone' => 'America/New_York'],
    ]);

    $type = new DatetimeFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(DateTimePicker::class);
});

it('applies seconds setting', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'logged_at',
        'field_type' => 'datetime',
        'settings' => ['seconds' => true],
    ]);

    $type = new DatetimeFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(DateTimePicker::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'published_at',
        'field_type' => 'datetime',
        'settings' => [],
    ]);

    $type = new DatetimeFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = DatetimeFieldType::settingsSchema();

    expect($schema)->toBeArray();
    expect($schema)->not->toBeEmpty();
});
