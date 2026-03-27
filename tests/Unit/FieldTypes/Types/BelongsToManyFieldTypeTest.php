<?php

use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\BelongsToManyFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(BelongsToManyFieldType::$key)->toBe('belongs_to_many');
    expect(BelongsToManyFieldType::$label)->toBe('Belongs To Many');
    expect(BelongsToManyFieldType::$icon)->toBe('heroicon-o-arrows-right-left');
    expect(BelongsToManyFieldType::$eavCast)->toBe(EavCast::Json);
    expect(BelongsToManyFieldType::$category)->toBe('relational');
});

it('generates a multiple Select component', function () {
    $field = StudioField::factory()->make(['column_name' => 'tags', 'field_type' => 'belongs_to_many', 'settings' => ['related_collection' => 'tags']]);
    $type = new BelongsToManyFieldType($field);
    expect($type->toFilamentComponent())->toBeInstanceOf(Select::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make(['column_name' => 'tags', 'field_type' => 'belongs_to_many', 'settings' => []]);
    $type = new BelongsToManyFieldType($field);
    expect($type->toTableColumn())->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    expect(BelongsToManyFieldType::settingsSchema())->toBeArray()->not->toBeEmpty();
});
