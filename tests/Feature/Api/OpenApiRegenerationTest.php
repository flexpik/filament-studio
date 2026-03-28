<?php

use Dedoc\Scramble\Support\Generator\InfoObject;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Flexpik\FilamentStudio\Api\OpenApi\StudioDocumentTransformer;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;

beforeEach(function () {
    config()->set('filament-studio.api.enabled', true);
});

function generateOpenApiSpec(): array
{
    $openApi = OpenApi::make('3.1.0');
    $openApi->setInfo((new InfoObject('Studio API'))->setVersion('1.0.0'));
    (new StudioDocumentTransformer)($openApi);

    return $openApi->toArray();
}

it('reflects new fields added to a collection', function () {
    $collection = StudioCollection::factory()->apiEnabled()->create(['slug' => 'posts']);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_system' => false,
    ]);

    $specBefore = generateOpenApiSpec();
    $storeProps = $specBefore['paths']['/studio/posts']['post']['requestBody']['content']['application/json']['schema']['properties']['data']['properties'];
    expect($storeProps)->toHaveKey('title')
        ->and($storeProps)->not->toHaveKey('summary');

    // Add a new field
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'summary',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_system' => false,
    ]);

    $specAfter = generateOpenApiSpec();
    $storePropsAfter = $specAfter['paths']['/studio/posts']['post']['requestBody']['content']['application/json']['schema']['properties']['data']['properties'];
    expect($storePropsAfter)->toHaveKey('title')
        ->and($storePropsAfter)->toHaveKey('summary');
});

it('reflects collection api_enabled toggled on', function () {
    $collection = StudioCollection::factory()->create(['slug' => 'notes', 'api_enabled' => false]);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'body',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_system' => false,
    ]);

    $specBefore = generateOpenApiSpec();
    expect($specBefore)->not->toHaveKey('paths');

    $collection->update(['api_enabled' => true]);

    $specAfter = generateOpenApiSpec();
    expect($specAfter['paths'])->toHaveKey('/studio/notes');
});

it('reflects collection api_enabled toggled off', function () {
    $collection = StudioCollection::factory()->apiEnabled()->create(['slug' => 'events']);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_system' => false,
    ]);

    $specBefore = generateOpenApiSpec();
    expect($specBefore['paths'])->toHaveKey('/studio/events');

    $collection->update(['api_enabled' => false]);

    $specAfter = generateOpenApiSpec();
    expect($specAfter)->not->toHaveKey('paths');
});
