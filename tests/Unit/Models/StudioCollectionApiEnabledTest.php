<?php

use Flexpik\FilamentStudio\Models\StudioCollection;

it('casts api_enabled to boolean', function () {
    $collection = StudioCollection::factory()->create(['api_enabled' => 1]);

    expect($collection->api_enabled)->toBeTrue();
});

it('defaults api_enabled to false in factory', function () {
    $collection = StudioCollection::factory()->create();

    expect($collection->api_enabled)->toBeFalse();
});

it('scopes apiEnabled to only api-enabled collections', function () {
    StudioCollection::factory()->create(['api_enabled' => false]);
    $enabled = StudioCollection::factory()->create(['api_enabled' => true]);

    $results = StudioCollection::query()->apiEnabled()->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($enabled->id);
});

it('has apiEnabled factory state', function () {
    $collection = StudioCollection::factory()->apiEnabled()->create();

    expect($collection->api_enabled)->toBeTrue();
});
