<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Actions\Collections\DeleteCollection;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioFieldOption;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;

beforeEach(fn () => $this->action = new DeleteCollection);

it('deletes the collection and cascades to fields, records, values', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    $field = StudioField::factory()->for($c, 'collection')->create(['tenant_id' => 1]);
    StudioFieldOption::factory()->for($field, 'field')->create(['tenant_id' => 1]);
    $record = StudioRecord::factory()->for($c, 'collection')->create(['tenant_id' => 1]);
    StudioValue::factory()->for($record, 'record')->for($field, 'field')->create();

    ($this->action)('products', 1);

    expect(StudioCollection::count())->toBe(0)
        ->and(StudioField::count())->toBe(0)
        ->and(StudioFieldOption::count())->toBe(0)
        ->and(StudioRecord::count())->toBe(0)
        ->and(StudioValue::count())->toBe(0);
});

it('throws StudioNotFoundException for unknown slug', function () {
    expect(fn () => ($this->action)('missing', 1))->toThrow(StudioNotFoundException::class);
});
