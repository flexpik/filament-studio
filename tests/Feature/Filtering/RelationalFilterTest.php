<?php

use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Filtering\FilterGroup;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

beforeEach(function () {
    // Authors collection
    $this->authorsCollection = StudioCollection::factory()->create(['name' => 'authors', 'slug' => 'authors']);
    $this->authorNameField = StudioField::factory()->create([
        'collection_id' => $this->authorsCollection->id,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => EavCast::Text,
    ]);

    // Articles collection
    $this->articlesCollection = StudioCollection::factory()->create(['name' => 'articles', 'slug' => 'articles']);
    $this->titleField = StudioField::factory()->create([
        'collection_id' => $this->articlesCollection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => EavCast::Text,
    ]);
    $this->authorField = StudioField::factory()->create([
        'collection_id' => $this->articlesCollection->id,
        'column_name' => 'author',
        'field_type' => 'belongs_to',
        'eav_cast' => EavCast::Text,
        'settings' => ['related_collection' => 'authors'],
    ]);

    // Create author: John
    $john = StudioRecord::factory()->create(['collection_id' => $this->authorsCollection->id]);
    StudioValue::factory()->create(['record_id' => $john->id, 'field_id' => $this->authorNameField->id, 'val_text' => 'John Doe']);

    // Create author: Jane
    $jane = StudioRecord::factory()->create(['collection_id' => $this->authorsCollection->id]);
    StudioValue::factory()->create(['record_id' => $jane->id, 'field_id' => $this->authorNameField->id, 'val_text' => 'Jane Smith']);

    // Article by John
    $a1 = StudioRecord::factory()->create(['collection_id' => $this->articlesCollection->id]);
    StudioValue::factory()->create(['record_id' => $a1->id, 'field_id' => $this->titleField->id, 'val_text' => 'PHP 8.4']);
    StudioValue::factory()->create(['record_id' => $a1->id, 'field_id' => $this->authorField->id, 'val_text' => $john->uuid]);

    // Article by Jane
    $a2 = StudioRecord::factory()->create(['collection_id' => $this->articlesCollection->id]);
    StudioValue::factory()->create(['record_id' => $a2->id, 'field_id' => $this->titleField->id, 'val_text' => 'Laravel 12']);
    StudioValue::factory()->create(['record_id' => $a2->id, 'field_id' => $this->authorField->id, 'val_text' => $jane->uuid]);

    EavQueryBuilder::invalidateFieldCache();
});

it('filters by related field value using related_field key', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            [
                'field' => 'author',
                'operator' => 'eq',
                'value' => 'John Doe',
                'related_field' => 'name',
            ],
        ],
    ]);

    $results = EavQueryBuilder::for($this->articlesCollection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->title)->toBe('PHP 8.4');
});

it('filters by related field with contains operator', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            [
                'field' => 'author',
                'operator' => 'contains',
                'value' => 'Smith',
                'related_field' => 'name',
            ],
        ],
    ]);

    $results = EavQueryBuilder::for($this->articlesCollection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->title)->toBe('Laravel 12');
});

it('returns no results when related field filter matches nothing', function () {
    $tree = FilterGroup::fromArray([
        'logic' => 'and',
        'rules' => [
            [
                'field' => 'author',
                'operator' => 'eq',
                'value' => 'Nonexistent Author',
                'related_field' => 'name',
            ],
        ],
    ]);

    $results = EavQueryBuilder::for($this->articlesCollection)
        ->applyFilterTree($tree)
        ->get();

    expect($results)->toHaveCount(0);
});
