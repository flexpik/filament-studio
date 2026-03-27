<?php

use Filament\Forms\Components\Placeholder;
use Flexpik\FilamentStudio\FieldTypes\Types\CalloutFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(CalloutFieldType::$key)->toBe('callout');
    expect(CalloutFieldType::$label)->toBe('Callout');
    expect(CalloutFieldType::$icon)->toBe('heroicon-o-information-circle');
    expect(CalloutFieldType::$category)->toBe('presentation');
});

it('generates a Placeholder component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'info_callout',
        'field_type' => 'callout',
        'settings' => ['content' => 'Please read the terms carefully.'],
    ]);

    $type = new CalloutFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Placeholder::class);
});

it('returns null for table column', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'callout_1',
        'field_type' => 'callout',
        'settings' => [],
    ]);

    $type = new CalloutFieldType($field);

    expect($type->toTableColumn())->toBeNull();
});

it('returns null for filter', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'callout_1',
        'field_type' => 'callout',
        'settings' => [],
    ]);

    $type = new CalloutFieldType($field);

    expect($type->toFilter())->toBeNull();
});

it('returns a settings schema', function () {
    $schema = CalloutFieldType::settingsSchema();

    expect($schema)->toBeArray();
    expect($schema)->not->toBeEmpty();
});
