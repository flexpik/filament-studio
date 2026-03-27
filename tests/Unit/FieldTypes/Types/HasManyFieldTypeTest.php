<?php

use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\HasManyFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(HasManyFieldType::$key)->toBe('has_many');
    expect(HasManyFieldType::$label)->toBe('Has Many');
    expect(HasManyFieldType::$icon)->toBe('heroicon-o-rectangle-stack');
    expect(HasManyFieldType::$eavCast)->toBe(EavCast::Json);
    expect(HasManyFieldType::$category)->toBe('relational');
});

it('generates a Placeholder component (read-only)', function () {
    $field = StudioField::factory()->make(['column_name' => 'comments', 'field_type' => 'has_many', 'settings' => ['related_collection' => 'comments']]);
    $type = new HasManyFieldType($field);
    expect($type->toFilamentComponent())->toBeInstanceOf(Placeholder::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make(['column_name' => 'comments', 'field_type' => 'has_many', 'settings' => []]);
    $type = new HasManyFieldType($field);
    expect($type->toTableColumn())->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    expect(HasManyFieldType::settingsSchema())->toBeArray()->not->toBeEmpty();
});
