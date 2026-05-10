<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Actions\Fields\CreateField;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->action = new CreateField);

it('creates a text field with auto-resolved eav_cast', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);

    $f = ($this->action)('products', ['column_name' => 'sku', 'field_type' => 'text'], 1);

    expect($f->column_name)->toBe('sku')
        ->and($f->field_type)->toBe('text')
        ->and($f->eav_cast->value)->toBe('text')
        ->and($f->sort_order)->toBeInt();
});

it('creates a select field with inline options', function () {
    StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);

    $f = ($this->action)('products', [
        'column_name' => 'status',
        'field_type' => 'select',
        'options' => [
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'archived', 'label' => 'Archived'],
        ],
    ], 1);

    expect($f->options)->toHaveCount(2);
});

it('throws on unknown collection', function () {
    expect(fn () => ($this->action)('ghost', ['column_name' => 'x', 'field_type' => 'text'], 1))
        ->toThrow(StudioNotFoundException::class);
});

it('throws on unknown field type', function () {
    StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);

    expect(fn () => ($this->action)('products', ['column_name' => 'x', 'field_type' => 'no_such_type'], 1))
        ->toThrow(ValidationException::class);
});
