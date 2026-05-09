<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Actions\FieldOptions\SetFieldOptions;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioFieldOption;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->action = new SetFieldOptions);

it('replaces all options on a select field', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    $f = StudioField::factory()->for($c, 'collection')->create([
        'column_name' => 'status', 'field_type' => 'select', 'tenant_id' => 1,
    ]);
    StudioFieldOption::factory()->for($f, 'field')->create(['value' => 'old', 'tenant_id' => 1]);

    ($this->action)('products', 'status', [
        ['value' => 'a', 'label' => 'A'],
        ['value' => 'b', 'label' => 'B'],
    ], 1);

    expect(StudioFieldOption::pluck('value')->all())->toBe(['a', 'b']);
});

it('rejects setting options on a non-select field', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($c, 'collection')->create([
        'column_name' => 'sku', 'field_type' => 'text', 'tenant_id' => 1,
    ]);

    expect(fn () => ($this->action)('products', 'sku', [['value' => 'x', 'label' => 'X']], 1))
        ->toThrow(ValidationException::class);
});

it('throws StudioNotFoundException for unknown field', function () {
    StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);

    expect(fn () => ($this->action)('products', 'ghost', [['value' => 'x', 'label' => 'X']], 1))
        ->toThrow(StudioNotFoundException::class);
});
