<?php

use Filament\Forms\Components\TagsInput;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\TagsFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(TagsFieldType::$key)->toBe('tags');
    expect(TagsFieldType::$label)->toBe('Tags');
    expect(TagsFieldType::$icon)->toBe('heroicon-o-tag');
    expect(TagsFieldType::$eavCast)->toBe(EavCast::Json);
    expect(TagsFieldType::$category)->toBe('structured');
});

it('generates a TagsInput component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'tags',
        'field_type' => 'tags',
        'settings' => [],
    ]);

    $type = new TagsFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TagsInput::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'tags',
        'field_type' => 'tags',
        'settings' => [],
    ]);

    $type = new TagsFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = TagsFieldType::settingsSchema();

    expect($schema)->toBeArray();
});
