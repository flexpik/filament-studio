<?php

use Filament\Schemas\Components\Section;
use Flexpik\FilamentStudio\FieldTypes\Types\SectionHeaderFieldType;
use Flexpik\FilamentStudio\Models\StudioField;

it('has correct static properties', function () {
    expect(SectionHeaderFieldType::$key)->toBe('section_header');
    expect(SectionHeaderFieldType::$label)->toBe('Section Header');
    expect(SectionHeaderFieldType::$icon)->toBe('heroicon-o-rectangle-group');
    expect(SectionHeaderFieldType::$category)->toBe('presentation');
});

it('generates a Section component', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'personal_info_section',
        'field_type' => 'section_header',
        'settings' => [
            'section_label' => 'Personal Information',
            'description' => 'Enter your personal details',
            'collapsible' => true,
            'collapsed' => false,
            'columns' => 2,
        ],
    ]);

    $type = new SectionHeaderFieldType($field);
    $component = $type->toFilamentComponent();

    expect($component)->toBeInstanceOf(Section::class);
});

it('returns null for table column (presentation only)', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'section_1',
        'field_type' => 'section_header',
        'settings' => [],
    ]);

    $type = new SectionHeaderFieldType($field);

    expect($type->toTableColumn())->toBeNull();
});

it('returns null for filter', function () {
    $field = StudioField::factory()->make([
        'column_name' => 'section_1',
        'field_type' => 'section_header',
        'settings' => [],
    ]);

    $type = new SectionHeaderFieldType($field);

    expect($type->toFilter())->toBeNull();
});

it('returns a settings schema', function () {
    $schema = SectionHeaderFieldType::settingsSchema();

    expect($schema)->toBeArray();
    expect($schema)->not->toBeEmpty();
});
