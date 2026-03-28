<?php

use Dedoc\Scramble\Support\Generator\InfoObject;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Flexpik\FilamentStudio\Api\OpenApi\StudioDocumentTransformer;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;

beforeEach(function () {
    config()->set('filament-studio.api.enabled', true);
    config()->set('filament-studio.api.prefix', 'api/studio');
});

function buildOpenApi(): OpenApi
{
    $openApi = OpenApi::make('3.1.0');
    $openApi->setInfo((new InfoObject('Studio API'))->setVersion('1.0.0'));

    return $openApi;
}

it('adds per-collection paths for api-enabled collections', function () {
    $collection = StudioCollection::factory()->apiEnabled()->create(['slug' => 'products', 'label' => 'Product', 'label_plural' => 'Products']);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_required' => true,
        'is_system' => false,
    ]);

    $openApi = buildOpenApi();
    (new StudioDocumentTransformer)($openApi);

    $spec = $openApi->toArray();

    expect($spec['paths'])->toHaveKey('/studio/products')
        ->and($spec['paths']['/studio/products'])->toHaveKeys(['get', 'post']);

    expect($spec['paths'])->toHaveKey('/studio/products/{uuid}')
        ->and($spec['paths']['/studio/products/{uuid}'])->toHaveKeys(['get', 'put', 'delete']);
});

it('excludes collections with api_enabled=false', function () {
    StudioCollection::factory()->create(['slug' => 'hidden-stuff', 'api_enabled' => false]);

    $openApi = buildOpenApi();
    (new StudioDocumentTransformer)($openApi);

    $spec = $openApi->toArray();

    expect($spec)->not->toHaveKey('paths');
});

it('includes field-specific schema in store request body', function () {
    $collection = StudioCollection::factory()->apiEnabled()->create(['slug' => 'articles']);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_required' => true,
        'is_system' => false,
    ]);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'views',
        'label' => 'Views',
        'field_type' => 'integer',
        'eav_cast' => 'integer',
        'is_required' => false,
        'is_system' => false,
    ]);

    $openApi = buildOpenApi();
    (new StudioDocumentTransformer)($openApi);

    $spec = $openApi->toArray();
    $storeSchema = $spec['paths']['/studio/articles']['post']['requestBody']['content']['application/json']['schema'];

    expect($storeSchema['properties']['data']['properties'])->toHaveKeys(['title', 'views'])
        ->and($storeSchema['properties']['data']['properties']['title']['type'])->toBe('string')
        ->and($storeSchema['properties']['data']['properties']['views']['type'])->toBe('integer')
        ->and($storeSchema['properties']['data']['required'])->toContain('title');
});

it('skips system fields in the schema', function () {
    $collection = StudioCollection::factory()->apiEnabled()->create(['slug' => 'tasks']);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_system' => false,
    ]);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'internal_id',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_system' => true,
    ]);

    $openApi = buildOpenApi();
    (new StudioDocumentTransformer)($openApi);

    $spec = $openApi->toArray();
    $storeSchema = $spec['paths']['/studio/tasks']['post']['requestBody']['content']['application/json']['schema'];

    expect($storeSchema['properties']['data']['properties'])->toHaveKey('name')
        ->and($storeSchema['properties']['data']['properties'])->not->toHaveKey('internal_id');
});

it('tags operations with the collection label', function () {
    $collection = StudioCollection::factory()->apiEnabled()->create([
        'slug' => 'products',
        'label' => 'Product',
        'label_plural' => 'Products',
    ]);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_system' => false,
    ]);

    $openApi = buildOpenApi();
    (new StudioDocumentTransformer)($openApi);

    $spec = $openApi->toArray();

    expect($spec['paths']['/studio/products']['get']['tags'])->toContain('Products');
});
