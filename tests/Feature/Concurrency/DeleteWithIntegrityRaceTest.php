<?php

// tests/Feature/Concurrency/DeleteWithIntegrityRaceTest.php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

beforeEach(function () {
    EavQueryBuilder::invalidateFieldCache();
});

it('deleteWithIntegrity is atomic — referential check and delete happen in one transaction', function () {
    $parentCol = StudioCollection::factory()->forTenant(1)->create(['slug' => 'authors']);
    StudioField::factory()->create([
        'collection_id' => $parentCol->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $childCol = StudioCollection::factory()->forTenant(1)->create(['slug' => 'books']);
    StudioField::factory()->create([
        'collection_id' => $childCol->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);
    $refField = StudioField::factory()->create([
        'collection_id' => $childCol->id,
        'tenant_id' => 1,
        'column_name' => 'author',
        'field_type' => 'belongs_to',
        'eav_cast' => 'text',
        'settings' => [
            'related_collection' => 'authors',
            'on_delete' => 'set_null',
        ],
    ]);

    $author = EavQueryBuilder::for($parentCol)->tenant(1)->create(['name' => 'Author A']);
    $book = EavQueryBuilder::for($childCol)->tenant(1)->create([
        'title' => 'Book 1',
        'author' => $author->uuid,
    ]);

    EavQueryBuilder::for($parentCol)->tenant(1)->deleteWithIntegrity($author->uuid);

    expect(StudioRecord::find($author->id))->toBeNull();

    $bookAuthorValue = StudioValue::where('record_id', $book->id)
        ->where('field_id', $refField->id)
        ->first();

    expect($bookAuthorValue->val_text)->toBeNull();
});

it('deleteWithIntegrity respects restrict and throws before deleting', function () {
    $parentCol = StudioCollection::factory()->forTenant(1)->create(['slug' => 'categories']);
    StudioField::factory()->create([
        'collection_id' => $parentCol->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $childCol = StudioCollection::factory()->forTenant(1)->create(['slug' => 'items']);
    StudioField::factory()->create([
        'collection_id' => $childCol->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);
    StudioField::factory()->create([
        'collection_id' => $childCol->id,
        'tenant_id' => 1,
        'column_name' => 'category',
        'field_type' => 'belongs_to',
        'eav_cast' => 'text',
        'settings' => [
            'related_collection' => 'categories',
            'on_delete' => 'restrict',
        ],
    ]);

    $category = EavQueryBuilder::for($parentCol)->tenant(1)->create(['name' => 'Cat A']);
    EavQueryBuilder::for($childCol)->tenant(1)->create([
        'title' => 'Item 1',
        'category' => $category->uuid,
    ]);

    expect(fn () => EavQueryBuilder::for($parentCol)->tenant(1)->deleteWithIntegrity($category->uuid))
        ->toThrow(\RuntimeException::class, 'Cannot delete record: it is referenced by other records');

    expect(StudioRecord::find($category->id))->not->toBeNull();
});
