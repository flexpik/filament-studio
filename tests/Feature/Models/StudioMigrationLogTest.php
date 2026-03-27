<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioMigrationLog;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

it('can be created with factory', function () {
    $log = StudioMigrationLog::factory()->create();

    expect($log)
        ->toBeInstanceOf(StudioMigrationLog::class)
        ->operation->not->toBeEmpty();
});

it('casts before_state and after_state as arrays', function () {
    $log = StudioMigrationLog::factory()->create([
        'before_state' => ['name' => 'old'],
        'after_state' => ['name' => 'new'],
    ]);

    expect($log->before_state)->toBe(['name' => 'old'])
        ->and($log->after_state)->toBe(['name' => 'new']);
});

it('belongs to a collection', function () {
    $collection = StudioCollection::factory()->create();
    $log = StudioMigrationLog::factory()->create(['collection_id' => $collection->id]);

    expect($log->collection->id)->toBe($collection->id);
});

it('has a field relationship', function () {
    $log = StudioMigrationLog::factory()->create();

    expect($log->field())->toBeInstanceOf(BelongsTo::class);
});

it('scopes by tenant', function () {
    StudioMigrationLog::factory()->create(['tenant_id' => 1]);
    StudioMigrationLog::factory()->create(['tenant_id' => 2]);

    expect(StudioMigrationLog::forTenant(1)->count())->toBe(1);
});
