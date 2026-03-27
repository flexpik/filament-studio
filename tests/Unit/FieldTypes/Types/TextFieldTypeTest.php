<?php

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\TextFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(TextFieldType::$key)->toBe('text');
    expect(TextFieldType::$label)->toBe('Text Input');
    expect(TextFieldType::$icon)->toBe('heroicon-o-bars-3-bottom-left');
    expect(TextFieldType::$eavCast)->toBe(EavCast::Text);
    expect(TextFieldType::$category)->toBe('text');
});

it('generates a TextInput component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'name',
        'field_type' => 'text',
        'settings' => [],
    ]);

    $type = new TextFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TextInput::class);
});

it('applies email subtype', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'email',
        'field_type' => 'text',
        'settings' => ['subtype' => 'email'],
    ]);

    $type = new TextFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TextInput::class);
});

it('applies url subtype', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'website',
        'field_type' => 'text',
        'settings' => ['subtype' => 'url'],
    ]);

    $type = new TextFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TextInput::class);
});

it('applies tel subtype', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'phone',
        'field_type' => 'text',
        'settings' => ['subtype' => 'tel'],
    ]);

    $type = new TextFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TextInput::class);
});

it('applies numeric subtype with min and max', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'quantity',
        'field_type' => 'text',
        'settings' => ['subtype' => 'numeric', 'min' => 1, 'max' => 100, 'step' => 1],
    ]);

    $type = new TextFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TextInput::class);
});

it('applies prefix and suffix', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'price',
        'field_type' => 'text',
        'settings' => ['prefix' => '$', 'suffix' => 'USD'],
    ]);

    $type = new TextFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TextInput::class);
});

it('applies mask setting', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'ssn',
        'field_type' => 'text',
        'settings' => ['mask' => '999-99-9999'],
    ]);

    $type = new TextFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TextInput::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'title',
        'field_type' => 'text',
        'settings' => [],
    ]);

    $type = new TextFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = TextFieldType::settingsSchema();

    expect($schema)->toBeArray();
    expect($schema)->not->toBeEmpty();
});
