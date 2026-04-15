<?php

use Flexpik\FilamentStudio\Api\StudioApiRouteRegistrar;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Support\Str;

beforeEach(function () {
    StudioApiRouteRegistrar::register();

    config(['filament-studio.api.enabled' => true]);
    config(['filament-studio.locales.enabled' => true]);
    config(['filament-studio.locales.available' => ['en', 'fr', 'de']]);
    config(['filament-studio.locales.default' => 'en']);

    $this->collection = StudioCollection::factory()->apiEnabled()->create([
        'slug' => 'products',
        'supported_locales' => ['en', 'fr'],
        'default_locale' => 'en',
    ]);

    $this->titleField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_translatable' => true,
    ]);

    $this->priceField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'price',
        'field_type' => 'decimal',
        'eav_cast' => 'decimal',
        'is_translatable' => false,
    ]);

    EavQueryBuilder::invalidateFieldCache($this->collection->id);

    $this->plainKey = Str::random(64);
    StudioApiKey::factory()->fullAccess()->create([
        'key' => hash('sha256', $this->plainKey),
    ]);

    $this->headers = [
        'X-Api-Key' => $this->plainKey,
        'Accept' => 'application/json',
    ];

    // Create a record with EN values
    $this->record = EavQueryBuilder::for($this->collection)
        ->locale('en')
        ->create(['title' => 'Product', 'price' => 29.99]);

    // Add FR translation
    EavQueryBuilder::for($this->collection)
        ->locale('fr')
        ->update($this->record->id, ['title' => 'Produit']);
});

it('returns data in requested locale via query param', function () {
    $response = $this->getJson(
        "/api/studio/products/{$this->record->uuid}?locale=fr",
        $this->headers
    );

    $response->assertOk()
        ->assertJsonPath('data.data.title', 'Produit')
        ->assertJsonPath('_meta.locale', 'fr');
});

it('returns data in requested locale via X-Locale header', function () {
    $response = $this->getJson(
        "/api/studio/products/{$this->record->uuid}",
        array_merge($this->headers, ['X-Locale' => 'fr'])
    );

    $response->assertOk()
        ->assertJsonPath('data.data.title', 'Produit');
});

it('falls back to en value for fr record with missing fr translation on a field', function () {
    // Create a record that only has an EN title (no FR translation)
    $enOnlyRecord = EavQueryBuilder::for($this->collection)
        ->locale('en')
        ->create(['title' => 'EN Only Product', 'price' => 49.99]);

    $response = $this->getJson(
        "/api/studio/products/{$enOnlyRecord->uuid}?locale=fr",
        $this->headers
    );

    $response->assertOk()
        ->assertJsonPath('data.data.title', 'EN Only Product')
        ->assertJsonPath('_meta.fallbacks', ['title']);
});

it('returns all locales when all_locales=true', function () {
    $response = $this->getJson(
        "/api/studio/products/{$this->record->uuid}?all_locales=true",
        $this->headers
    );

    $response->assertOk()
        ->assertJsonPath('data.data.title.en', 'Product')
        ->assertJsonPath('data.data.title.fr', 'Produit');
});

it('stores data in specified locale', function () {
    $response = $this->postJson(
        "/api/studio/products?locale=fr",
        ['data' => ['title' => 'Nouveau Produit', 'price' => 19.99]],
        $this->headers
    );

    $response->assertCreated();

    $newRecord = StudioRecord::latest('id')->first();
    $frData = EavQueryBuilder::for($this->collection)->locale('fr')->getRecordData($newRecord);

    expect($frData['title'])->toBe('Nouveau Produit');
});

it('includes _meta.locale in show response', function () {
    $response = $this->getJson(
        "/api/studio/products/{$this->record->uuid}",
        $this->headers
    );

    $response->assertOk()
        ->assertJsonPath('_meta.locale', 'en');
});

it('includes _meta.locale in store response', function () {
    $response = $this->postJson(
        "/api/studio/products",
        ['data' => ['title' => 'New Product', 'price' => 9.99]],
        $this->headers
    );

    $response->assertCreated()
        ->assertJsonPath('_meta.locale', 'en');
});

it('includes _meta.locale in update response', function () {
    $response = $this->putJson(
        "/api/studio/products/{$this->record->uuid}",
        ['data' => ['title' => 'Updated Product']],
        $this->headers
    );

    $response->assertOk()
        ->assertJsonPath('_meta.locale', 'en');
});

it('returns locale-aware data in index listing via query param', function () {
    $response = $this->getJson(
        '/api/studio/products?locale=fr',
        $this->headers
    );

    $response->assertOk();

    $firstRecord = $response->json('data.0');
    expect($firstRecord['data']['title'])->toBe('Produit');
});

it('updates data in specified locale and preserves other locales', function () {
    $response = $this->putJson(
        "/api/studio/products/{$this->record->uuid}?locale=fr",
        ['data' => ['title' => 'Produit Mis A Jour']],
        $this->headers
    );

    $response->assertOk()
        ->assertJsonPath('_meta.locale', 'fr');

    // Verify EN is untouched
    $enData = EavQueryBuilder::for($this->collection)
        ->locale('en')
        ->getRecordData($this->record);

    expect($enData['title'])->toBe('Product');

    // Verify FR is updated
    $frData = EavQueryBuilder::for($this->collection)
        ->locale('fr')
        ->getRecordData($this->record);

    expect($frData['title'])->toBe('Produit Mis A Jour');
});

it('stores data with default locale when no locale specified', function () {
    $response = $this->postJson(
        '/api/studio/products',
        ['data' => ['title' => 'Default Locale Product', 'price' => 9.99]],
        $this->headers
    );

    $response->assertCreated()
        ->assertJsonPath('_meta.locale', 'en');

    $uuid = $response->json('data.uuid');
    $newRecord = StudioRecord::where('uuid', $uuid)->first();

    $titleValue = StudioValue::where('record_id', $newRecord->id)
        ->where('field_id', $this->titleField->id)
        ->first();

    expect($titleValue->locale)->toBe('en');
});

it('returns non-translatable fields as flat values in all_locales response', function () {
    $response = $this->getJson(
        "/api/studio/products/{$this->record->uuid}?all_locales=true",
        $this->headers
    );

    $response->assertOk();

    $price = $response->json('data.data.price');
    // Price is non-translatable — should be a flat value, not a locale map
    expect($price)->not->toBeArray();
    expect((float) $price)->toBe(29.99);
});

it('includes correct locale in _meta when using X-Locale header', function () {
    $response = $this->getJson(
        "/api/studio/products/{$this->record->uuid}",
        array_merge($this->headers, ['X-Locale' => 'fr'])
    );

    $response->assertOk()
        ->assertJsonPath('_meta.locale', 'fr')
        ->assertJsonPath('_meta.fallbacks', []);
});
