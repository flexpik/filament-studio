<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('can be created with factory', function () {
    $record = StudioRecord::factory()->create();

    expect($record)
        ->toBeInstanceOf(StudioRecord::class)
        ->uuid->not->toBeEmpty();
});

it('auto-generates uuid on creation', function () {
    $record = StudioRecord::factory()->create();

    expect($record->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

it('belongs to a collection', function () {
    $collection = StudioCollection::factory()->create();
    $record = StudioRecord::factory()->create(['collection_id' => $collection->id]);

    expect($record->collection->id)->toBe($collection->id);
});

it('has values relationship', function () {
    $record = StudioRecord::factory()->create();

    expect($record->values())->toBeInstanceOf(HasMany::class);
});

it('has versions relationship', function () {
    $record = StudioRecord::factory()->create();

    expect($record->versions())->toBeInstanceOf(HasMany::class);
});

it('supports soft deletes', function () {
    $record = StudioRecord::factory()->create();
    $record->delete();

    expect(StudioRecord::withTrashed()->find($record->id))->not->toBeNull()
        ->and(StudioRecord::find($record->id))->toBeNull();
});

it('scopes by tenant', function () {
    StudioRecord::factory()->create(['tenant_id' => 1]);
    StudioRecord::factory()->create(['tenant_id' => 2]);

    expect(StudioRecord::forTenant(1)->count())->toBe(1);
});
