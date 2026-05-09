<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Actions\Fields\ReorderFields;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->action = new ReorderFields);

it('reorders fields by column_names', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($c, 'collection')->create(['column_name' => 'a', 'sort_order' => 0, 'tenant_id' => 1]);
    StudioField::factory()->for($c, 'collection')->create(['column_name' => 'b', 'sort_order' => 1, 'tenant_id' => 1]);
    StudioField::factory()->for($c, 'collection')->create(['column_name' => 'c', 'sort_order' => 2, 'tenant_id' => 1]);

    ($this->action)('products', ['c', 'a', 'b'], 1);

    expect(StudioField::query()->where('column_name', 'c')->value('sort_order'))->toBe(0)
        ->and(StudioField::query()->where('column_name', 'a')->value('sort_order'))->toBe(1)
        ->and(StudioField::query()->where('column_name', 'b')->value('sort_order'))->toBe(2);
});

it('rejects a list that does not exactly match existing fields', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($c, 'collection')->create(['column_name' => 'a', 'tenant_id' => 1]);

    expect(fn () => ($this->action)('products', ['a', 'ghost'], 1))->toThrow(ValidationException::class);
});
