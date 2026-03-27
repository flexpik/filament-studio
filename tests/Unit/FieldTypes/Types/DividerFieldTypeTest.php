<?php

use Filament\Forms\Components\Placeholder;
use Flexpik\FilamentStudio\FieldTypes\Types\DividerFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(DividerFieldType::$key)->toBe('divider');
    expect(DividerFieldType::$label)->toBe('Divider');
    expect(DividerFieldType::$icon)->toBe('heroicon-o-minus');
    expect(DividerFieldType::$category)->toBe('presentation');
});

it('generates a Placeholder component with hr', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'separator_1',
        'field_type' => 'divider',
        'settings' => [],
    ]);

    $type = new DividerFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Placeholder::class);
});

it('returns null for table column', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'separator_1',
        'field_type' => 'divider',
        'settings' => [],
    ]);

    $type = new DividerFieldType($field);

    expect($type->toTableColumn())->toBeNull();
});

it('returns null for filter', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'separator_1',
        'field_type' => 'divider',
        'settings' => [],
    ]);

    $type = new DividerFieldType($field);

    expect($type->toFilter())->toBeNull();
});

it('returns an empty settings schema', function () {
    $schema = DividerFieldType::settingsSchema();

    expect($schema)->toBeArray();
    expect($schema)->toBeEmpty();
});
