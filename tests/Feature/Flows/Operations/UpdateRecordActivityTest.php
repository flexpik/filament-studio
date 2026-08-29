<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Operations\Records\UpdateRecordActivity;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->collection = StudioCollection::factory()->create(['slug' => 'people', 'tenant_id' => 1]);
    StudioField::factory()->for($this->collection, 'collection')->create(['column_name' => 'name', 'field_type' => 'text']);
    $this->record = StudioRecord::factory()->for($this->collection, 'collection')->create(['tenant_id' => 1]);
});

it('updates a record by id', function () {
    $ctx = makeOperationContext(
        config: ['collection' => 'people', 'id' => $this->record->id, 'data' => ['name' => 'Sera-2']],
        tenantId: '1',
    );

    $result = (new UpdateRecordActivity)->execute($ctx);

    expect($result->output())->toMatchArray(['updated' => 1, 'id' => $this->record->id]);
});

it('refuses to update across tenants', function () {
    $ctx = makeOperationContext(
        config: ['collection' => 'people', 'id' => $this->record->id, 'data' => ['name' => 'evil']],
        tenantId: '2',
    );

    (new UpdateRecordActivity)->execute($ctx);
})->throws(ModelNotFoundException::class);
