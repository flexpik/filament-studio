<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Actions\Collections\CreateCollection;
use Flexpik\FilamentStudio\Mcp\Exceptions\IntegrityException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->action = new CreateCollection);

it('creates a collection with auto-generated slug', function () {
    $c = ($this->action)(['name' => 'Products'], tenantId: 1);

    expect($c->name)->toBe('Products')
        ->and($c->slug)->toBe('products')
        ->and($c->tenant_id)->toBe(1);
});

it('honors an explicit slug', function () {
    $c = ($this->action)(['name' => 'Sales Products', 'slug' => 'items'], tenantId: 1);
    expect($c->slug)->toBe('items');
});

it('rejects an invalid slug pattern', function () {
    expect(fn () => ($this->action)(['name' => 'X', 'slug' => '_bad'], tenantId: 1))
        ->toThrow(ValidationException::class);
});

it('throws IntegrityException on duplicate slug within tenant', function () {
    StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);

    expect(fn () => ($this->action)(['name' => 'Products'], tenantId: 1))
        ->toThrow(IntegrityException::class);
});

it('creates inline fields in one shot', function () {
    $c = ($this->action)([
        'name' => 'Products',
        'fields' => [
            ['column_name' => 'sku', 'field_type' => 'text'],
            ['column_name' => 'price', 'field_type' => 'decimal'],
        ],
    ], tenantId: 1);

    expect($c->fields)->toHaveCount(2)
        ->and($c->fields->pluck('column_name')->all())->toBe(['sku', 'price']);
});
