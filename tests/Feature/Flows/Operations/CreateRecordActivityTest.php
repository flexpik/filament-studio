<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Operations\Records\CreateRecordActivity;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create(['slug' => 'people', 'tenant_id' => 1]);
    StudioField::factory()->for($this->collection, 'collection')->create(['column_name' => 'name', 'field_type' => 'text']);
});

it('creates a record under the configured collection scoped to accountability tenant', function () {
    $ctx = makeOperationContext(
        config: ['collection' => 'people', 'data' => ['name' => 'Sera']],
        tenantId: '1',
    );

    $result = (new CreateRecordActivity)->execute($ctx);

    expect($result->output())->toHaveKey('id');
    $record = StudioRecord::find($result->output()['id']);
    expect($record)->not->toBeNull();
    expect($record->tenant_id)->toBe(1);
});

it('throws InvalidArgumentException when collection slug unknown', function () {
    $ctx = makeOperationContext(
        config: ['collection' => 'missing', 'data' => []],
        tenantId: '1',
    );

    (new CreateRecordActivity)->execute($ctx);
})->throws(InvalidArgumentException::class);
