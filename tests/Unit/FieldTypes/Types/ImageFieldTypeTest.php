<?php

use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\ImageFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(ImageFieldType::$key)->toBe('image');
    expect(ImageFieldType::$label)->toBe('Image Upload');
    expect(ImageFieldType::$icon)->toBe('heroicon-o-photo');
    expect(ImageFieldType::$eavCast)->toBe(EavCast::Text);
    expect(ImageFieldType::$category)->toBe('file');
});

it('generates an image FileUpload component', function () {
    $field = StudioField::factory()->make(['column_name' => 'photo', 'field_type' => 'image', 'settings' => []]);
    $type = new ImageFieldType($field);
    expect($type->toFilamentComponent())->toBeInstanceOf(FileUpload::class);
});

it('applies crop aspect ratio setting', function () {
    $field = StudioField::factory()->make(['column_name' => 'banner', 'field_type' => 'image', 'settings' => ['image_crop_aspect_ratio' => '16:9']]);
    $type = new ImageFieldType($field);
    expect($type->toFilamentComponent())->toBeInstanceOf(FileUpload::class);
});

it('applies resize target settings', function () {
    $field = StudioField::factory()->make(['column_name' => 'thumbnail', 'field_type' => 'image', 'settings' => ['image_resize_target_width' => 800, 'image_resize_target_height' => 600]]);
    $type = new ImageFieldType($field);
    expect($type->toFilamentComponent())->toBeInstanceOf(FileUpload::class);
});

it('generates an ImageColumn for tables', function () {
    $field = StudioField::factory()->make(['column_name' => 'photo', 'field_type' => 'image', 'settings' => []]);
    $type = new ImageFieldType($field);
    expect($type->toTableColumn())->toBeInstanceOf(ImageColumn::class);
});

it('returns a settings schema', function () {
    expect(ImageFieldType::settingsSchema())->toBeArray()->not->toBeEmpty();
});
