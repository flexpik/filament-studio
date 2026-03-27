<?php

use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

// -- 1. eav_cast change --

it('transitions eav_cast from text to integer', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'eav_cast' => 'text',
    ]);

    expect($field->eav_cast)->toBe(EavCast::Text);

    $field->update(['eav_cast' => 'integer']);
    $field->refresh();

    expect($field->eav_cast)->toBe(EavCast::Integer);
});

it('transitions eav_cast from integer back to text', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'eav_cast' => 'integer',
    ]);

    expect($field->eav_cast)->toBe(EavCast::Integer);

    $field->update(['eav_cast' => 'text']);
    $field->refresh();

    expect($field->eav_cast)->toBe(EavCast::Text);
});

it('invalidates field cache when eav_cast changes', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'eav_cast' => 'text',
    ]);

    // Prime the cache
    $cached = EavQueryBuilder::getCachedFields($collection);
    expect($cached)->toHaveCount(1)
        ->and($cached->first()->eav_cast)->toBe(EavCast::Text);

    // Update eav_cast (triggers cache invalidation via boot hook)
    $field->update(['eav_cast' => 'integer']);

    // Cache was invalidated, so a fresh fetch should reflect the change
    $refreshed = EavQueryBuilder::getCachedFields($collection);
    expect($refreshed)->toHaveCount(1)
        ->and($refreshed->first()->eav_cast)->toBe(EavCast::Integer);
});

// -- 2. is_required toggle --

it('transitions is_required from false to true', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_required' => false,
    ]);

    expect($field->is_required)->toBeFalse();

    $field->update(['is_required' => true]);
    $field->refresh();

    expect($field->is_required)->toBeTrue();
});

it('transitions is_required from true to false', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->required()->create([
        'collection_id' => $collection->id,
    ]);

    expect($field->is_required)->toBeTrue();

    $field->update(['is_required' => false]);
    $field->refresh();

    expect($field->is_required)->toBeFalse();
});

// -- 3. is_unique toggle --

it('transitions is_unique from false to true', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_unique' => false,
    ]);

    expect($field->is_unique)->toBeFalse();

    $field->update(['is_unique' => true]);
    $field->refresh();

    expect($field->is_unique)->toBeTrue();
});

// -- 4. is_nullable toggle --

it('transitions is_nullable from false to true', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_nullable' => false,
    ]);

    expect($field->is_nullable)->toBeFalse();

    $field->update(['is_nullable' => true]);
    $field->refresh();

    expect($field->is_nullable)->toBeTrue();
});

it('transitions is_nullable from true to false', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_nullable' => true,
    ]);

    expect($field->is_nullable)->toBeTrue();

    $field->update(['is_nullable' => false]);
    $field->refresh();

    expect($field->is_nullable)->toBeFalse();
});

// -- 5. is_indexed toggle --

it('transitions is_indexed from false to true', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_indexed' => false,
    ]);

    expect($field->is_indexed)->toBeFalse();

    $field->update(['is_indexed' => true]);
    $field->refresh();

    expect($field->is_indexed)->toBeTrue();
});

it('transitions is_indexed from true to false', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_indexed' => true,
    ]);

    expect($field->is_indexed)->toBeTrue();

    $field->update(['is_indexed' => false]);
    $field->refresh();

    expect($field->is_indexed)->toBeFalse();
});

// -- 6. is_system toggle --

it('transitions is_system from false to true', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_system' => false,
    ]);

    expect($field->is_system)->toBeFalse();

    $field->update(['is_system' => true]);
    $field->refresh();

    expect($field->is_system)->toBeTrue();
});

// -- 7. is_filterable toggle --

it('transitions is_filterable from false to true', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_filterable' => false,
    ]);

    expect($field->is_filterable)->toBeFalse();

    $field->update(['is_filterable' => true]);
    $field->refresh();

    expect($field->is_filterable)->toBeTrue();
});

it('transitions is_filterable from true to false', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_filterable' => true,
    ]);

    expect($field->is_filterable)->toBeTrue();

    $field->update(['is_filterable' => false]);
    $field->refresh();

    expect($field->is_filterable)->toBeFalse();
});

// -- 8. is_hidden_in_form toggle --

it('transitions is_hidden_in_form from false to true', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_hidden_in_form' => false,
    ]);

    expect($field->is_hidden_in_form)->toBeFalse();

    $field->update(['is_hidden_in_form' => true]);
    $field->refresh();

    expect($field->is_hidden_in_form)->toBeTrue();
});

it('transitions is_hidden_in_form from true to false', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_hidden_in_form' => true,
    ]);

    expect($field->is_hidden_in_form)->toBeTrue();

    $field->update(['is_hidden_in_form' => false]);
    $field->refresh();

    expect($field->is_hidden_in_form)->toBeFalse();
});

// -- 9. is_hidden_in_table toggle --

it('transitions is_hidden_in_table from false to true', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_hidden_in_table' => false,
    ]);

    expect($field->is_hidden_in_table)->toBeFalse();

    $field->update(['is_hidden_in_table' => true]);
    $field->refresh();

    expect($field->is_hidden_in_table)->toBeTrue();
});

it('transitions is_hidden_in_table from true to false', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_hidden_in_table' => true,
    ]);

    expect($field->is_hidden_in_table)->toBeTrue();

    $field->update(['is_hidden_in_table' => false]);
    $field->refresh();

    expect($field->is_hidden_in_table)->toBeFalse();
});

// -- 10. is_disabled_on_create toggle --

it('transitions is_disabled_on_create from false to true', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_disabled_on_create' => false,
    ]);

    expect($field->is_disabled_on_create)->toBeFalse();

    $field->update(['is_disabled_on_create' => true]);
    $field->refresh();

    expect($field->is_disabled_on_create)->toBeTrue();
});

it('transitions is_disabled_on_create from true to false', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_disabled_on_create' => true,
    ]);

    expect($field->is_disabled_on_create)->toBeTrue();

    $field->update(['is_disabled_on_create' => false]);
    $field->refresh();

    expect($field->is_disabled_on_create)->toBeFalse();
});

// -- 11. is_disabled_on_edit toggle --

it('transitions is_disabled_on_edit from false to true', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_disabled_on_edit' => false,
    ]);

    expect($field->is_disabled_on_edit)->toBeFalse();

    $field->update(['is_disabled_on_edit' => true]);
    $field->refresh();

    expect($field->is_disabled_on_edit)->toBeTrue();
});

it('transitions is_disabled_on_edit from true to false', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'is_disabled_on_edit' => true,
    ]);

    expect($field->is_disabled_on_edit)->toBeTrue();

    $field->update(['is_disabled_on_edit' => false]);
    $field->refresh();

    expect($field->is_disabled_on_edit)->toBeFalse();
});

// -- 12. Delete with side effects (cache invalidation) --

it('invalidates field cache when a field is deleted', function () {
    $collection = StudioCollection::factory()->create();
    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
    ]);

    // Prime the cache via public API
    $cachedBefore = EavQueryBuilder::getCachedFields($collection);
    expect($cachedBefore)->toHaveCount(1);

    // Delete the field (triggers cache invalidation via boot hook)
    $field->delete();

    // Cache was invalidated by the deleted hook, so getCachedFields
    // will re-query and return an empty collection
    $cachedAfter = EavQueryBuilder::getCachedFields($collection);
    expect($cachedAfter)->toHaveCount(0);
});

it('invalidates field cache when one of multiple fields is deleted', function () {
    $collection = StudioCollection::factory()->create();

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'first',
    ]);
    $second = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'second',
    ]);

    // Prime the cache
    $cachedBefore = EavQueryBuilder::getCachedFields($collection);
    expect($cachedBefore)->toHaveCount(2);

    // Delete one field
    $second->delete();

    // Cache was invalidated, fresh query returns remaining field
    $cachedAfter = EavQueryBuilder::getCachedFields($collection);
    expect($cachedAfter)->toHaveCount(1)
        ->and($cachedAfter->first()->column_name)->toBe('first');
});
