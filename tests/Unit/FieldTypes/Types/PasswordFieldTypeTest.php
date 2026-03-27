<?php

use Filament\Forms\Components\TextInput;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\PasswordFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(PasswordFieldType::$key)->toBe('password');
    expect(PasswordFieldType::$label)->toBe('Password');
    expect(PasswordFieldType::$icon)->toBe('heroicon-o-lock-closed');
    expect(PasswordFieldType::$eavCast)->toBe(EavCast::Text);
    expect(PasswordFieldType::$category)->toBe('text');
});

it('generates a password TextInput component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'secret',
        'field_type' => 'password',
        'settings' => [],
    ]);

    $type = new PasswordFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(TextInput::class);
});

it('returns null for table column (passwords should not display)', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'secret',
        'field_type' => 'password',
        'settings' => [],
    ]);

    $type = new PasswordFieldType($field);

    expect($type->toTableColumn())->toBeNull();
});

it('returns null for filter', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'secret',
        'field_type' => 'password',
        'settings' => [],
    ]);

    $type = new PasswordFieldType($field);

    expect($type->toFilter())->toBeNull();
});

it('returns a settings schema', function () {
    $schema = PasswordFieldType::settingsSchema();

    expect($schema)->toBeArray();
});
