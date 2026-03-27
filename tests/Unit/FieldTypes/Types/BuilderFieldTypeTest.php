<?php

use Filament\Forms\Components\Builder;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\BuilderFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(BuilderFieldType::$key)->toBe('builder');
    expect(BuilderFieldType::$label)->toBe('Builder');
    expect(BuilderFieldType::$icon)->toBe('heroicon-o-cube');
    expect(BuilderFieldType::$eavCast)->toBe(EavCast::Json);
    expect(BuilderFieldType::$category)->toBe('structured');
});

it('generates a Builder component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'content_blocks',
        'field_type' => 'builder',
        'settings' => ['blocks' => []],
    ]);

    $type = new BuilderFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Builder::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'content_blocks',
        'field_type' => 'builder',
        'settings' => [],
    ]);

    $type = new BuilderFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = BuilderFieldType::settingsSchema();

    expect($schema)->toBeArray();
});
