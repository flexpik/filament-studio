<?php

use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\ToggleFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(ToggleFieldType::$key)->toBe('toggle');
    expect(ToggleFieldType::$label)->toBe('Toggle');
    expect(ToggleFieldType::$icon)->toBe('heroicon-o-bolt');
    expect(ToggleFieldType::$eavCast)->toBe(EavCast::Boolean);
    expect(ToggleFieldType::$category)->toBe('boolean');
});

it('generates a Toggle component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'is_active',
        'field_type' => 'toggle',
        'settings' => [],
    ]);

    $type = new ToggleFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Toggle::class);
});

it('applies on/off label settings', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'is_published',
        'field_type' => 'toggle',
        'settings' => ['on_label' => 'Published', 'off_label' => 'Draft', 'on_color' => 'success', 'off_color' => 'danger'],
    ]);

    $type = new ToggleFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Toggle::class);
});

it('generates an IconColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'is_active',
        'field_type' => 'toggle',
        'settings' => [],
    ]);

    $type = new ToggleFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(IconColumn::class);
});

it('returns a settings schema', function () {
    $schema = ToggleFieldType::settingsSchema();

    expect($schema)->toBeArray();
    expect($schema)->not->toBeEmpty();
});

it('includes on_label and off_label in settings schema', function () {
    $schema = ToggleFieldType::settingsSchema();

    $fieldNames = array_map(fn ($component) => $component->getName(), $schema);

    expect($fieldNames)->toContain('on_label');
    expect($fieldNames)->toContain('off_label');
});
