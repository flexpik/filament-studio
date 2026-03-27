<?php

use Flexpik\FilamentStudio\Api\Resources\RecordResource;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Http\Request;

it('transforms a record into API-friendly format', function () {
    $collection = StudioCollection::factory()->create(['slug' => 'posts']);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 1,
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'published',
        'label' => 'Published',
        'field_type' => 'toggle',
        'eav_cast' => 'boolean',
        'sort_order' => 2,
    ]);

    $record = EavQueryBuilder::for($collection)->create([
        'title' => 'Hello World',
        'published' => true,
    ]);

    $request = Request::create('/api/studio/posts/'.$record->uuid);
    $request->attributes->set('studio_collection', $collection);

    $resource = new RecordResource($record);
    $resource->setCollection($collection);
    $resolved = $resource->toArray($request);

    expect($resolved)->toHaveKeys(['uuid', 'data', 'created_at', 'updated_at']);
    expect($resolved['uuid'])->toBe($record->uuid);
    expect($resolved['data']['title'])->toBe('Hello World');
    expect($resolved['data']['published'])->toBeTrue();
});
