<?php

declare(strict_types=1);

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Flexpik\FilamentStudio\Mcp\Support\FilamentSchemaToJsonSchema;

it('translates TextInput to a JSON-Schema string', function () {
    $components = [
        TextInput::make('label')->required()->maxLength(100),
    ];

    $schema = (new FilamentSchemaToJsonSchema)->translate($components);

    expect($schema)->toMatchArray([
        'type' => 'object',
        'properties' => [
            'label' => [
                'type' => 'string',
                'maxLength' => 100,
            ],
        ],
        'required' => ['label'],
    ]);
});

it('translates Toggle to a JSON-Schema boolean', function () {
    $schema = (new FilamentSchemaToJsonSchema)->translate([
        Toggle::make('is_required')->default(false),
    ]);

    expect($schema['properties']['is_required'])->toMatchArray([
        'type' => 'boolean',
        'default' => false,
    ]);
});

it('translates Select with options to JSON-Schema enum', function () {
    $schema = (new FilamentSchemaToJsonSchema)->translate([
        Select::make('alignment')->options(['left' => 'Left', 'right' => 'Right']),
    ]);

    expect($schema['properties']['alignment'])->toMatchArray([
        'type' => 'string',
        'enum' => ['left', 'right'],
    ]);
});

it('degrades unknown components to description-only entries', function () {
    $unknown = new class('mystery_field') extends Field {};

    $schema = (new FilamentSchemaToJsonSchema)->translate([$unknown]);

    expect($schema['properties']['mystery_field'])->toMatchArray([
        'type' => 'unknown',
        'description' => 'This setting is configurable in the Filament UI but is not represented in JSON Schema. Refer to the field-type documentation for valid values.',
    ]);
});
