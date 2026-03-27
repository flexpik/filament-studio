<?php

use Filament\Forms\Components\Hidden;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\HiddenFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(HiddenFieldType::$key)->toBe('hidden');
    expect(HiddenFieldType::$label)->toBe('Hidden');
    expect(HiddenFieldType::$icon)->toBe('heroicon-o-eye-slash');
    expect(HiddenFieldType::$eavCast)->toBe(EavCast::Text);
    expect(HiddenFieldType::$category)->toBe('presentation');
});

it('generates a Hidden component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'tracking_id',
        'field_type' => 'hidden',
        'settings' => [],
    ]);

    $type = new HiddenFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Hidden::class);
});

it('returns null for table column', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'tracking_id',
        'field_type' => 'hidden',
        'settings' => [],
    ]);

    $type = new HiddenFieldType($field);

    expect($type->toTableColumn())->toBeNull();
});

it('returns null for filter', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'tracking_id',
        'field_type' => 'hidden',
        'settings' => [],
    ]);

    $type = new HiddenFieldType($field);

    expect($type->toFilter())->toBeNull();
});

it('returns an empty settings schema', function () {
    $schema = HiddenFieldType::settingsSchema();

    expect($schema)->toBeArray();
    expect($schema)->toBeEmpty();
});
