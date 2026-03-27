<?php

use Flexpik\FilamentStudio\Enums\SortDirection;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioMigrationLog;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Illuminate\Database\QueryException;

it('can be created with factory', function () {
    $collection = StudioCollection::factory()->create();

    expect($collection)
        ->toBeInstanceOf(StudioCollection::class)
        ->name->not->toBeEmpty()
        ->label->not->toBeEmpty()
        ->slug->not->toBeEmpty();
});

it('casts attributes correctly', function () {
    $collection = StudioCollection::factory()->create([
        'is_singleton' => true,
        'is_hidden' => false,
        'enable_versioning' => true,
        'enable_soft_deletes' => false,
        'sort_direction' => 'desc',
        'translations' => ['en' => 'Products'],
        'settings' => ['key' => 'value'],
    ]);

    expect($collection->is_singleton)->toBeTrue()
        ->and($collection->is_hidden)->toBeFalse()
        ->and($collection->enable_versioning)->toBeTrue()
        ->and($collection->enable_soft_deletes)->toBeFalse()
        ->and($collection->sort_direction)->toBe(SortDirection::Desc)
        ->and($collection->translations)->toBe(['en' => 'Products'])
        ->and($collection->settings)->toBe(['key' => 'value']);
});

it('has fields relationship', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create(['collection_id' => $collection->id]);

    expect($collection->fields)->toHaveCount(1)
        ->and($collection->fields->first()->id)->toBe($field->id);
});

it('has records relationship', function () {
    $collection = StudioCollection::factory()->create();
    $record = StudioRecord::factory()->create(['collection_id' => $collection->id]);

    expect($collection->records)->toHaveCount(1)
        ->and($collection->records->first()->id)->toBe($record->id);
});

it('has migrationLogs relationship', function () {
    $collection = StudioCollection::factory()->create();
    StudioMigrationLog::factory()->create(['collection_id' => $collection->id]);

    expect($collection->migrationLogs)->toHaveCount(1);
});

it('scopes visible collections', function () {
    StudioCollection::factory()->create(['is_hidden' => false]);
    StudioCollection::factory()->create(['is_hidden' => true]);

    expect(StudioCollection::visible()->count())->toBe(1);
});

it('scopes by tenant', function () {
    StudioCollection::factory()->create(['tenant_id' => 1]);
    StudioCollection::factory()->create(['tenant_id' => 2]);

    expect(StudioCollection::forTenant(1)->count())->toBe(1);
});

it('enforces unique name per tenant', function () {
    StudioCollection::factory()->create(['tenant_id' => 1, 'name' => 'products']);

    StudioCollection::factory()->create(['tenant_id' => 1, 'name' => 'products']);
})->throws(QueryException::class);

it('allows same name for different tenants', function () {
    StudioCollection::factory()->create(['tenant_id' => 1, 'name' => 'products', 'slug' => 'products']);
    $second = StudioCollection::factory()->create(['tenant_id' => 2, 'name' => 'products', 'slug' => 'products']);

    expect($second)->toBeInstanceOf(StudioCollection::class);
});
