<?php

use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\AvatarFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(AvatarFieldType::$key)->toBe('avatar');
    expect(AvatarFieldType::$label)->toBe('Avatar');
    expect(AvatarFieldType::$icon)->toBe('heroicon-o-user-circle');
    expect(AvatarFieldType::$eavCast)->toBe(EavCast::Text);
    expect(AvatarFieldType::$category)->toBe('file');
});

it('generates a circular FileUpload component', function () {
    $field = StudioField::factory()->make(['column_name' => 'profile_photo', 'field_type' => 'avatar', 'settings' => []]);
    $type = new AvatarFieldType($field);
    expect($type->toFilamentComponent())->toBeInstanceOf(FileUpload::class);
});

it('generates a circular ImageColumn for tables', function () {
    $field = StudioField::factory()->make(['column_name' => 'profile_photo', 'field_type' => 'avatar', 'settings' => []]);
    $type = new AvatarFieldType($field);
    expect($type->toTableColumn())->toBeInstanceOf(ImageColumn::class);
});

it('returns a settings schema', function () {
    expect(AvatarFieldType::settingsSchema())->toBeArray();
});
