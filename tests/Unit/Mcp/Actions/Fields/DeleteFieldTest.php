<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Actions\Fields\DeleteField;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioFieldOption;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;

beforeEach(fn () => $this->action = new DeleteField);

it('deletes the field and cascades to options and values', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    $f = StudioField::factory()->for($c, 'collection')->create(['column_name' => 'status', 'tenant_id' => 1]);
    StudioFieldOption::factory()->for($f, 'field')->create(['tenant_id' => 1]);
    $r = StudioRecord::factory()->for($c, 'collection')->create(['tenant_id' => 1]);
    StudioValue::factory()->for($r, 'record')->for($f, 'field')->create();

    ($this->action)('products', 'status', 1);

    expect(StudioField::count())->toBe(0)
        ->and(StudioFieldOption::count())->toBe(0)
        ->and(StudioValue::count())->toBe(0);
});

it('throws if the field does not exist', function () {
    StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);

    expect(fn () => ($this->action)('products', 'ghost', 1))->toThrow(StudioNotFoundException::class);
});
