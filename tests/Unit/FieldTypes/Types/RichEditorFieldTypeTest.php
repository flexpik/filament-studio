<?php

use Filament\Forms\Components\RichEditor;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\RichEditorFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(RichEditorFieldType::$key)->toBe('rich_editor');
    expect(RichEditorFieldType::$label)->toBe('Rich Editor');
    expect(RichEditorFieldType::$icon)->toBe('heroicon-o-document-magnifying-glass');
    expect(RichEditorFieldType::$eavCast)->toBe(EavCast::Text);
    expect(RichEditorFieldType::$category)->toBe('text');
});

it('generates a RichEditor component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'content',
        'field_type' => 'rich_editor',
        'settings' => [],
    ]);

    $type = new RichEditorFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(RichEditor::class);
});

it('applies toolbar buttons setting', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'body',
        'field_type' => 'rich_editor',
        'settings' => ['toolbar_buttons' => ['bold', 'italic', 'h2']],
    ]);

    $type = new RichEditorFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(RichEditor::class);
});

it('applies attachment settings', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'body',
        'field_type' => 'rich_editor',
        'settings' => [
            'attachment_disk' => 'public',
            'attachment_directory' => 'rich-uploads',
        ],
    ]);

    $type = new RichEditorFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(RichEditor::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'content',
        'field_type' => 'rich_editor',
        'settings' => [],
    ]);

    $type = new RichEditorFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = RichEditorFieldType::settingsSchema();

    expect($schema)->toBeArray();
    expect($schema)->not->toBeEmpty();
});
