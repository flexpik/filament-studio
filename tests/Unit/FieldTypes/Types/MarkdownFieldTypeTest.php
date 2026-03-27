<?php

use Filament\Forms\Components\MarkdownEditor;
use Filament\Tables\Columns\TextColumn;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\Types\MarkdownFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(MarkdownFieldType::$key)->toBe('markdown');
    expect(MarkdownFieldType::$label)->toBe('Markdown Editor');
    expect(MarkdownFieldType::$icon)->toBe('heroicon-o-hashtag');
    expect(MarkdownFieldType::$eavCast)->toBe(EavCast::Text);
    expect(MarkdownFieldType::$category)->toBe('text');
});

it('generates a MarkdownEditor component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'content',
        'field_type' => 'markdown',
        'settings' => [],
    ]);

    $type = new MarkdownFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(MarkdownEditor::class);
});

it('generates a TextColumn for tables', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'content',
        'field_type' => 'markdown',
        'settings' => [],
    ]);

    $type = new MarkdownFieldType($field);
    $column = $type->toTableColumn();

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('returns a settings schema', function () {
    $schema = MarkdownFieldType::settingsSchema();

    expect($schema)->toBeArray();
});
