<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Operations\Records\DeleteRecordActivity;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create(['slug' => 'people', 'tenant_id' => 1]);
    $this->record = StudioRecord::factory()->for($this->collection, 'collection')->create(['tenant_id' => 1]);
});

it('deletes a record by id within tenant', function () {
    $ctx = makeOperationContext(
        config: ['collection' => 'people', 'id' => $this->record->id],
        tenantId: '1',
    );

    $result = (new DeleteRecordActivity)->execute($ctx);

    expect($result->output())->toBe(['deleted' => 1, 'id' => $this->record->id]);
    expect(StudioRecord::find($this->record->id))->toBeNull();
});

it('returns deleted=0 if no rows matched', function () {
    $ctx = makeOperationContext(
        config: ['collection' => 'people', 'id' => '00000000-0000-0000-0000-000000000000'],
        tenantId: '1',
    );

    $result = (new DeleteRecordActivity)->execute($ctx);

    expect($result->output()['deleted'])->toBe(0);
});

it('does not delete records from a different tenant', function () {
    $ctx = makeOperationContext(
        config: ['collection' => 'people', 'id' => $this->record->id],
        tenantId: '2',
    );

    $result = (new DeleteRecordActivity)->execute($ctx);

    expect($result->output()['deleted'])->toBe(0);
    expect(StudioRecord::find($this->record->id))->not->toBeNull();
});
