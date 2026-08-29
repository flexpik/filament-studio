<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Operations\Records\ReadRecordActivity;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create(['slug' => 'people', 'tenant_id' => 1]);
    StudioField::factory()->for($this->collection, 'collection')->create(['column_name' => 'name', 'field_type' => 'text']);
    $this->record = StudioRecord::factory()->for($this->collection, 'collection')->create(['tenant_id' => 1]);
});

it('reads a record by id', function () {
    $ctx = makeOperationContext(
        config: ['collection' => 'people', 'id' => $this->record->id],
        tenantId: '1',
    );

    $result = (new ReadRecordActivity)->execute($ctx);

    expect($result->output())->toHaveKey('record.id', $this->record->id);
});

it('reads multiple via filter group', function () {
    $ctx = makeOperationContext(
        config: ['collection' => 'people', 'filter' => ['logic' => 'and', 'rules' => []], 'multiple' => true],
        tenantId: '1',
    );

    $result = (new ReadRecordActivity)->execute($ctx);

    expect($result->output()['records'])->toBeArray()->not->toBeEmpty();
});

it('throws ModelNotFoundException when reading by id from a different tenant', function () {
    $ctx = makeOperationContext(
        config: ['collection' => 'people', 'id' => $this->record->id],
        tenantId: '2',
    );

    (new ReadRecordActivity)->execute($ctx);
})->throws(ModelNotFoundException::class);

it('reads first matching record when multiple is false and no id is given', function () {
    $ctx = makeOperationContext(
        config: ['collection' => 'people', 'filter' => ['logic' => 'and', 'rules' => []]],
        tenantId: '1',
    );

    $result = (new ReadRecordActivity)->execute($ctx);

    expect($result->output())->toHaveKey('record');
    expect($result->output()['record'])->not->toBeNull();
    expect($result->output()['record']['id'])->toBe($this->record->id);
});

it('scopes reads to tenant', function () {
    // Record with different tenant should not appear
    StudioRecord::factory()->for($this->collection, 'collection')->create(['tenant_id' => 2]);

    $ctx = makeOperationContext(
        config: ['collection' => 'people', 'filter' => ['logic' => 'and', 'rules' => []], 'multiple' => true],
        tenantId: '1',
    );

    $result = (new ReadRecordActivity)->execute($ctx);

    expect($result->output()['records'])->toHaveCount(1);
});
