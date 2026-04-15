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

it('adds locale parameters to index operation when locales are enabled', function () {
    config()->set('filament-studio.locales.enabled', true);
    config()->set('filament-studio.locales.available', ['en', 'fr', 'de']);

    $collection = StudioCollection::factory()->apiEnabled()->create(['slug' => 'articles']);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_system' => false,
        'is_translatable' => true,
    ]);

    $openApi = buildOpenApi();
    (new StudioDocumentTransformer)($openApi);

    $spec = $openApi->toArray();
    $params = $spec['paths']['/studio/articles']['get']['parameters'];
    $paramNames = array_column($params, 'name');

    expect($paramNames)->toContain('locale')
        ->and($paramNames)->toContain('X-Locale');
});

it('adds locale and all_locales parameters to show operation when locales are enabled', function () {
    config()->set('filament-studio.locales.enabled', true);
    config()->set('filament-studio.locales.available', ['en', 'fr']);

    $collection = StudioCollection::factory()->apiEnabled()->create(['slug' => 'pages']);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'body',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_system' => false,
        'is_translatable' => true,
    ]);

    $openApi = buildOpenApi();
    (new StudioDocumentTransformer)($openApi);

    $spec = $openApi->toArray();
    $params = $spec['paths']['/studio/pages/{uuid}']['get']['parameters'];
    $paramNames = array_column($params, 'name');

    expect($paramNames)->toContain('locale')
        ->and($paramNames)->toContain('X-Locale')
        ->and($paramNames)->toContain('all_locales');
});

it('adds locale parameters to store and update operations when locales are enabled', function () {
    config()->set('filament-studio.locales.enabled', true);
    config()->set('filament-studio.locales.available', ['en', 'fr']);

    $collection = StudioCollection::factory()->apiEnabled()->create(['slug' => 'posts']);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_system' => false,
        'is_translatable' => true,
    ]);

    $openApi = buildOpenApi();
    (new StudioDocumentTransformer)($openApi);

    $spec = $openApi->toArray();

    $storeParams = $spec['paths']['/studio/posts']['post']['parameters'];
    $storeParamNames = array_column($storeParams, 'name');
    expect($storeParamNames)->toContain('locale')
        ->and($storeParamNames)->toContain('X-Locale');

    $updateParams = $spec['paths']['/studio/posts/{uuid}']['put']['parameters'];
    $updateParamNames = array_column($updateParams, 'name');
    expect($updateParamNames)->toContain('locale')
        ->and($updateParamNames)->toContain('X-Locale');
});

it('does not add locale parameters when locales are disabled', function () {
    config()->set('filament-studio.locales.enabled', false);

    $collection = StudioCollection::factory()->apiEnabled()->create(['slug' => 'items']);
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
    $params = $spec['paths']['/studio/items']['get']['parameters'];
    $paramNames = array_column($params, 'name');

    expect($paramNames)->not->toContain('locale')
        ->and($paramNames)->not->toContain('X-Locale')
        ->and($paramNames)->not->toContain('all_locales');
});

it('includes _meta with locale info in show response schema when locales are enabled', function () {
    config()->set('filament-studio.locales.enabled', true);
    config()->set('filament-studio.locales.available', ['en', 'fr']);

    $collection = StudioCollection::factory()->apiEnabled()->create(['slug' => 'docs']);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'content',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_system' => false,
        'is_translatable' => true,
    ]);

    $openApi = buildOpenApi();
    (new StudioDocumentTransformer)($openApi);

    $spec = $openApi->toArray();
    $showResponse = $spec['paths']['/studio/docs/{uuid}']['get']['responses']['200']['content']['application/json']['schema'];

    expect($showResponse['properties'])->toHaveKey('_meta')
        ->and($showResponse['properties']['_meta']['properties'])->toHaveKey('locale')
        ->and($showResponse['properties']['_meta']['properties'])->toHaveKey('fallbacks');
});

it('does not include _meta in response schema when locales are disabled', function () {
    config()->set('filament-studio.locales.enabled', false);

    $collection = StudioCollection::factory()->apiEnabled()->create(['slug' => 'notes']);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'body',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_system' => false,
    ]);

    $openApi = buildOpenApi();
    (new StudioDocumentTransformer)($openApi);

    $spec = $openApi->toArray();
    $showResponse = $spec['paths']['/studio/notes/{uuid}']['get']['responses']['200']['content']['application/json']['schema'];

    expect($showResponse['properties'])->not->toHaveKey('_meta');
});

it('mentions locale support in operation descriptions when locales are enabled', function () {
    config()->set('filament-studio.locales.enabled', true);
    config()->set('filament-studio.locales.available', ['en', 'fr', 'de']);

    $collection = StudioCollection::factory()->apiEnabled()->create([
        'slug' => 'faq',
        'label' => 'FAQ',
        'label_plural' => 'FAQs',
    ]);
    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'question',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_system' => false,
        'is_translatable' => true,
    ]);

    $openApi = buildOpenApi();
    (new StudioDocumentTransformer)($openApi);

    $spec = $openApi->toArray();

    $showDesc = $spec['paths']['/studio/faq/{uuid}']['get']['description'];
    expect($showDesc)->toContain('locale');
});
