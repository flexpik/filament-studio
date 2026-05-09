<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Actions\Fields\UpdateField;
use Flexpik\FilamentStudio\Mcp\Exceptions\EavCastConflictException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->action = new UpdateField);

it('updates a field label', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($c, 'collection')->create([
        'column_name' => 'sku', 'tenant_id' => 1, 'label' => 'Old',
    ]);

    $f = ($this->action)('products', 'sku', ['label' => 'New SKU'], 1);
    expect($f->label)->toBe('New SKU');
});

it('rejects eav_cast change when values exist', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    $field = StudioField::factory()->for($c, 'collection')->create([
        'column_name' => 'price', 'tenant_id' => 1, 'eav_cast' => 'decimal', 'field_type' => 'decimal',
    ]);
    $record = StudioRecord::factory()->for($c, 'collection')->create(['tenant_id' => 1]);
    StudioValue::factory()->for($record, 'record')->for($field, 'field')->create(['val_decimal' => 9.99]);

    expect(fn () => ($this->action)('products', 'price', ['eav_cast' => 'text'], 1))
        ->toThrow(EavCastConflictException::class);
});

it('rejects column_name change', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($c, 'collection')->create(['column_name' => 'sku', 'tenant_id' => 1]);

    expect(fn () => ($this->action)('products', 'sku', ['column_name' => 'code'], 1))
        ->toThrow(ValidationException::class);
});
