<?php

use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\TextareaFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(TextareaFieldType::$key)->toBe('textarea');
    expect(TextareaFieldType::$label)->toBe('Textarea');
    expect(TextareaFieldType::$icon)->toBe('heroicon-o-document-text');
    expect(TextareaFieldType::$eavCast)->toBe(EavCast::Text);
    expect(TextareaFieldType::$category)->toBe('text');
});

it('generates a Textarea component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'description',
        'field_type' => 'textarea',
        'settings' => [],
    ]);

    $type = new TextareaFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Textarea::class);
});

it('applies rows setting', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'bio',
        'field_type' => 'textarea',
        'settings' => ['rows' => 8],
    ]);

    $type = new TextareaFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Textarea::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'notes',
        'field_type' => 'textarea',
        'settings' => [],
    ]);

    $type = new TextareaFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = TextareaFieldType::settingsSchema();

    expect($schema)->toBeArray();
});
