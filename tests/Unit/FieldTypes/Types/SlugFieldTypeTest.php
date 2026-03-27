<?php

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\SlugFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(SlugFieldType::$key)->toBe('slug');
    expect(SlugFieldType::$label)->toBe('Slug');
    expect(SlugFieldType::$icon)->toBe('heroicon-o-link');
    expect(SlugFieldType::$eavCast)->toBe(EavCast::Text);
    expect(SlugFieldType::$category)->toBe('text');
});

it('generates a TextInput component with live', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'slug',
        'field_type' => 'slug',
        'settings' => ['source_field' => 'title'],
    ]);

    $type = new SlugFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TextInput::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'slug',
        'field_type' => 'slug',
        'settings' => [],
    ]);

    $type = new SlugFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema with source_field', function () {
    $schema = SlugFieldType::settingsSchema();

    expect($schema)->toBeArray();
    expect($schema)->not->toBeEmpty();
});
