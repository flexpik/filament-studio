<?php

use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\RepeaterFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(RepeaterFieldType::$key)->toBe('repeater');
    expect(RepeaterFieldType::$label)->toBe('Repeater');
    expect(RepeaterFieldType::$icon)->toBe('heroicon-o-queue-list');
    expect(RepeaterFieldType::$eavCast)->toBe(EavCast::Json);
    expect(RepeaterFieldType::$category)->toBe('structured');
});

it('generates a Repeater component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'items',
        'field_type' => 'repeater',
        'settings' => ['schema' => []],
    ]);

    $type = new RepeaterFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Repeater::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'items',
        'field_type' => 'repeater',
        'settings' => [],
    ]);

    $type = new RepeaterFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = RepeaterFieldType::settingsSchema();

    expect($schema)->toBeArray();
});
