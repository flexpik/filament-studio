<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

it('resolves belongs_to display values via withRelated()', function () {
    $authorCollection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'authors']);
    $authorNameField = StudioField::factory()->create([
        'collection_id' => $authorCollection->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $author = StudioRecord::factory()->create([
        'collection_id' => $authorCollection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $author->id,
        'field_id' => $authorNameField->id,
        'val_text' => 'Jane Doe',
    ]);

    $postCollection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'posts']);
    $titleField = StudioField::factory()->create([
        'collection_id' => $postCollection->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);
    $authorField = StudioField::factory()->create([
        'collection_id' => $postCollection->id,
        'tenant_id' => 1,
        'column_name' => 'author',
        'field_type' => 'belongs_to',
        'eav_cast' => 'text',
        'settings' => [
            'related_collection' => 'authors',
            'display_field' => 'name',
            'tenant_scoped' => true,
        ],
    ]);

    $post = StudioRecord::factory()->create([
        'collection_id' => $postCollection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $post->id,
        'field_id' => $titleField->id,
        'val_text' => 'My Post',
    ]);
    StudioValue::factory()->create([
        'record_id' => $post->id,
        'field_id' => $authorField->id,
        'val_text' => $author->uuid,
    ]);

    $results = EavQueryBuilder::for($postCollection)
        ->tenant(1)
        ->select(['title', 'author'])
        ->withRelated('author', $authorCollection, displayField: 'name')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->title)->toBe('My Post')
        ->and($results->first()->author)->toBe($author->uuid)
        ->and($results->first()->author_display)->toBe('Jane Doe');
});

it('resolves belongs_to_many display values', function () {
    $tagCollection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'tags']);
    $tagNameField = StudioField::factory()->create([
        'collection_id' => $tagCollection->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $tag1 = StudioRecord::factory()->create(['collection_id' => $tagCollection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $tag1->id, 'field_id' => $tagNameField->id, 'val_text' => 'PHP']);

    $tag2 = StudioRecord::factory()->create(['collection_id' => $tagCollection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create(['record_id' => $tag2->id, 'field_id' => $tagNameField->id, 'val_text' => 'Laravel']);

    $articleCollection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'articles']);
    $tagsField = StudioField::factory()->create([
        'collection_id' => $articleCollection->id,
        'tenant_id' => 1,
        'column_name' => 'tags',
        'field_type' => 'belongs_to_many',
        'eav_cast' => 'json',
        'settings' => [
            'related_collection' => 'tags',
            'display_field' => 'name',
        ],
    ]);

    $article = StudioRecord::factory()->create(['collection_id' => $articleCollection->id, 'tenant_id' => 1]);
    StudioValue::factory()->create([
        'record_id' => $article->id,
        'field_id' => $tagsField->id,
        'val_json' => [$tag1->uuid, $tag2->uuid],
    ]);

    $results = EavQueryBuilder::for($articleCollection)
        ->tenant(1)
        ->select(['tags'])
        ->withRelated('tags', $tagCollection, displayField: 'name')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->tags)->toBe([$tag1->uuid, $tag2->uuid])
        ->and($results->first()->tags_display)->toContain('PHP')
        ->and($results->first()->tags_display)->toContain('Laravel');
});

it('restricts deletion when on_delete is restrict and references exist', function () {
    $authorCollection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'authors']);
    $authorNameField = StudioField::factory()->create([
        'collection_id' => $authorCollection->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $author = StudioRecord::factory()->create([
        'collection_id' => $authorCollection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $author->id,
        'field_id' => $authorNameField->id,
        'val_text' => 'Jane Doe',
    ]);

    $postCollection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'posts']);
    $authorField = StudioField::factory()->create([
        'collection_id' => $postCollection->id,
        'tenant_id' => 1,
        'column_name' => 'author',
        'field_type' => 'belongs_to',
        'eav_cast' => 'text',
        'settings' => [
            'related_collection' => 'authors',
            'display_field' => 'name',
            'on_delete' => 'restrict',
        ],
    ]);

    $post = StudioRecord::factory()->create([
        'collection_id' => $postCollection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $post->id,
        'field_id' => $authorField->id,
        'val_text' => $author->uuid,
    ]);

    expect(fn () => EavQueryBuilder::for($authorCollection)
        ->tenant(1)
        ->deleteWithIntegrity($author->uuid)
    )->toThrow(RuntimeException::class, 'Cannot delete record: it is referenced by other records');
});

it('sets null on referencing values when on_delete is set_null', function () {
    $authorCollection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'authors']);
    $authorNameField = StudioField::factory()->create([
        'collection_id' => $authorCollection->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $author = StudioRecord::factory()->create([
        'collection_id' => $authorCollection->id,
        'tenant_id' => 1,
    ]);
    StudioValue::factory()->create([
        'record_id' => $author->id,
        'field_id' => $authorNameField->id,
        'val_text' => 'Jane Doe',
    ]);

    $postCollection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'posts']);
    $authorField = StudioField::factory()->create([
        'collection_id' => $postCollection->id,
        'tenant_id' => 1,
        'column_name' => 'author',
        'field_type' => 'belongs_to',
        'eav_cast' => 'text',
        'settings' => [
            'related_collection' => 'authors',
            'display_field' => 'name',
            'on_delete' => 'set_null',
        ],
    ]);

    $post = StudioRecord::factory()->create([
        'collection_id' => $postCollection->id,
        'tenant_id' => 1,
    ]);
    $refValue = StudioValue::factory()->create([
        'record_id' => $post->id,
        'field_id' => $authorField->id,
        'val_text' => $author->uuid,
    ]);

    EavQueryBuilder::for($authorCollection)
        ->tenant(1)
        ->deleteWithIntegrity($author->uuid);

    expect(StudioRecord::find($author->id))->toBeNull();

    $refValue->refresh();
    expect($refValue->val_text)->toBeNull();
});

it('finds has_many reverse records for a given record UUID', function () {
    $authorCollection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'authors']);
    $author = StudioRecord::factory()->create([
        'collection_id' => $authorCollection->id,
        'tenant_id' => 1,
    ]);

    $postCollection = StudioCollection::factory()->create(['tenant_id' => 1, 'slug' => 'posts']);
    $titleField = StudioField::factory()->create([
        'collection_id' => $postCollection->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);
    $authorField = StudioField::factory()->create([
        'collection_id' => $postCollection->id,
        'tenant_id' => 1,
        'column_name' => 'author',
        'field_type' => 'belongs_to',
        'eav_cast' => 'text',
        'settings' => [
            'related_collection' => 'authors',
            'display_field' => 'name',
        ],
    ]);

    foreach (['Post A', 'Post B'] as $title) {
        $post = StudioRecord::factory()->create([
            'collection_id' => $postCollection->id,
            'tenant_id' => 1,
        ]);
        StudioValue::factory()->create([
            'record_id' => $post->id,
            'field_id' => $titleField->id,
            'val_text' => $title,
        ]);
        StudioValue::factory()->create([
            'record_id' => $post->id,
            'field_id' => $authorField->id,
            'val_text' => $author->uuid,
        ]);
    }

    $reverseRecords = EavQueryBuilder::for($postCollection)
        ->tenant(1)
        ->select(['title'])
        ->whereReferencing('author', $author->uuid)
        ->get();

    expect($reverseRecords)->toHaveCount(2)
        ->and($reverseRecords->pluck('title')->sort()->values()->all())
        ->toBe(['Post A', 'Post B']);
});
