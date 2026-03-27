<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Support\Collection;

mutates(EavQueryBuilder::class);

beforeEach(function () {
    // Authors collection
    $this->authorCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'authors',
        'slug' => 'authors',
    ]);

    $this->authorNameField = StudioField::factory()->create([
        'collection_id' => $this->authorCollection->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'label' => 'Name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    // Create authors
    $this->author1 = StudioRecord::factory()->create([
        'collection_id' => $this->authorCollection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $this->author1->id,
        'field_id' => $this->authorNameField->id,
        'val_text' => 'Jane Doe',
    ]);

    $this->author2 = StudioRecord::factory()->create([
        'collection_id' => $this->authorCollection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $this->author2->id,
        'field_id' => $this->authorNameField->id,
        'val_text' => 'John Smith',
    ]);

    // Articles collection with author FK
    $this->articleCollection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'articles',
        'slug' => 'articles',
    ]);

    $this->titleField = StudioField::factory()->create([
        'collection_id' => $this->articleCollection->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $this->authorField = StudioField::factory()->create([
        'collection_id' => $this->articleCollection->id,
        'tenant_id' => 1,
        'column_name' => 'author',
        'label' => 'Author',
        'field_type' => 'belongs_to',
        'eav_cast' => 'text', // Stores UUID of related record
    ]);

    // Create articles linked to authors by UUID
    $article1 = StudioRecord::factory()->create([
        'collection_id' => $this->articleCollection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $article1->id,
        'field_id' => $this->titleField->id,
        'val_text' => 'Laravel Guide',
    ]);
    StudioValue::factory()->create([
        'record_id' => $article1->id,
        'field_id' => $this->authorField->id,
        'val_text' => $this->author1->uuid,
    ]);

    $article2 = StudioRecord::factory()->create([
        'collection_id' => $this->articleCollection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $article2->id,
        'field_id' => $this->titleField->id,
        'val_text' => 'PHP Tips',
    ]);
    StudioValue::factory()->create([
        'record_id' => $article2->id,
        'field_id' => $this->authorField->id,
        'val_text' => $this->author2->uuid,
    ]);
});

it('resolves related record display values with withRelated', function () {
    $results = EavQueryBuilder::for($this->articleCollection)
        ->tenant(1)
        ->select(['title', 'author'])
        ->withRelated('author', $this->authorCollection, 'name')
        ->paginate(25);

    $items = $results->items();

    // Each item should have an author_display property with the resolved name
    $displayValues = array_map(fn ($item) => $item->author_display, $items);
    sort($displayValues);

    expect($displayValues)->toBe(['Jane Doe', 'John Smith']);
});

it('sets author_display to null when related record not found', function () {
    // Create article with invalid author UUID
    $article = StudioRecord::factory()->create([
        'collection_id' => $this->articleCollection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $article->id,
        'field_id' => $this->titleField->id,
        'val_text' => 'Orphaned Article',
    ]);
    StudioValue::factory()->create([
        'record_id' => $article->id,
        'field_id' => $this->authorField->id,
        'val_text' => '00000000-0000-0000-0000-000000000000',
    ]);

    $results = EavQueryBuilder::for($this->articleCollection)
        ->tenant(1)
        ->select(['title', 'author'])
        ->withRelated('author', $this->authorCollection, 'name')
        ->paginate(25);

    $orphanedItem = collect($results->items())->first(fn ($item) => $item->title === 'Orphaned Article');

    expect($orphanedItem->author_display)->toBeNull();
});

it('returns key-value pairs with pluck()', function () {
    $result = EavQueryBuilder::for($this->authorCollection)
        ->tenant(1)
        ->pluck('name', 'uuid');

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2)
        ->and($result->values()->sort()->values()->all())->toBe(['Jane Doe', 'John Smith']);
});

it('returns pluck with only value column', function () {
    $result = EavQueryBuilder::for($this->authorCollection)
        ->tenant(1)
        ->pluck('name');

    expect($result)->toHaveCount(2)
        ->and($result->sort()->values()->all())->toBe(['Jane Doe', 'John Smith']);
});
