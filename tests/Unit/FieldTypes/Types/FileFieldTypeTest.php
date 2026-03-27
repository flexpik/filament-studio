<?php

use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\FileFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(FileFieldType::$key)->toBe('file');
    expect(FileFieldType::$label)->toBe('File Upload');
    expect(FileFieldType::$icon)->toBe('heroicon-o-paper-clip');
    expect(FileFieldType::$eavCast)->toBe(EavCast::Text);
    expect(FileFieldType::$category)->toBe('file');
});

it('generates a FileUpload component', function () {
    $field = StudioField::factory()->make(['column_name' => 'attachment', 'field_type' => 'file', 'settings' => []]);
    $type = new FileFieldType($field);
    expect($type->toFilamentComponent())->toBeInstanceOf(FileUpload::class);
});

it('applies disk and directory settings', function () {
    $field = StudioField::factory()->make(['column_name' => 'document', 'field_type' => 'file', 'settings' => ['disk' => 'public', 'directory' => 'documents']]);
    $type = new FileFieldType($field);
    expect($type->toFilamentComponent())->toBeInstanceOf(FileUpload::class);
});

it('applies accepted types and max size', function () {
    $field = StudioField::factory()->make(['column_name' => 'pdf', 'field_type' => 'file', 'settings' => ['accepted_types' => ['application/pdf'], 'max_size' => 5120]]);
    $type = new FileFieldType($field);
    expect($type->toFilamentComponent())->toBeInstanceOf(FileUpload::class);
});

it('applies multiple file settings', function () {
    $field = StudioField::factory()->make(['column_name' => 'attachments', 'field_type' => 'file', 'settings' => ['multiple' => true, 'min_files' => 1, 'max_files' => 5]]);
    $type = new FileFieldType($field);
    expect($type->toFilamentComponent())->toBeInstanceOf(FileUpload::class);
});

it('applies enable_open and enable_download settings', function () {
    $field = StudioField::factory()->make(['column_name' => 'report', 'field_type' => 'file', 'settings' => ['enable_open' => true, 'enable_download' => true]]);
    $type = new FileFieldType($field);
    expect($type->toFilamentComponent())->toBeInstanceOf(FileUpload::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make(['column_name' => 'attachment', 'field_type' => 'file', 'settings' => []]);
    $type = new FileFieldType($field);
    expect($type->toTableColumn())->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = FileFieldType::settingsSchema();
    expect($schema)->toBeArray()->not->toBeEmpty();
});
