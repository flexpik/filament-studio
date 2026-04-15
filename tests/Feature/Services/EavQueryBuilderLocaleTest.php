<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Models\StudioValue;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

mutates(EavQueryBuilder::class);

beforeEach(function () {
    config(['filament-studio.locales.enabled' => true]);
    config(['filament-studio.locales.available' => ['en', 'fr', 'de']]);
    config(['filament-studio.locales.default' => 'en']);

    $this->collection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'name' => 'products',
        'slug' => 'products',
        'supported_locales' => ['en', 'fr'],
        'default_locale' => 'en',
    ]);

    $this->titleField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_translatable' => true,
    ]);

    $this->priceField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'price',
        'label' => 'Price',
        'field_type' => 'decimal',
        'eav_cast' => 'decimal',
        'is_translatable' => false,
    ]);
});

it('creates a record with locale on translatable fields', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('fr')
        ->create([
            'title' => 'Produit',
            'price' => 29.99,
        ]);

    $titleValue = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->titleField->id)
        ->first();
    expect($titleValue->locale)->toBe('fr')
        ->and($titleValue->val_text)->toBe('Produit');

    $priceValue = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->priceField->id)
        ->first();
    expect($priceValue->locale)->toBe('en')
        ->and((float) $priceValue->val_decimal)->toBe(29.99);
});

it('retrieves record data for specific locale', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('en')
        ->create(['title' => 'Product', 'price' => 29.99]);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('fr')
        ->update($record->id, ['title' => 'Produit']);

    $enData = EavQueryBuilder::for($this->collection)
        ->locale('en')
        ->getRecordData($record);
    expect($enData['title'])->toBe('Product')
        ->and((float) $enData['price'])->toBe(29.99);

    $frData = EavQueryBuilder::for($this->collection)
        ->locale('fr')
        ->getRecordData($record);
    expect($frData['title'])->toBe('Produit')
        ->and((float) $frData['price'])->toBe(29.99);
});

it('falls back to default locale when translation missing', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('en')
        ->create(['title' => 'Product', 'price' => 29.99]);

    $frData = EavQueryBuilder::for($this->collection)
        ->locale('fr')
        ->getRecordData($record);

    expect($frData['title'])->toBe('Product');
});

it('returns fallback metadata alongside data', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('en')
        ->create(['title' => 'Product', 'price' => 29.99]);

    $result = EavQueryBuilder::for($this->collection)
        ->locale('fr')
        ->getRecordDataWithMeta($record);

    expect($result['data']['title'])->toBe('Product')
        ->and($result['fallbacks'])->toContain('title');
});

it('updates only the specified locale for translatable fields', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('en')
        ->create(['title' => 'Product', 'price' => 29.99]);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('fr')
        ->update($record->id, ['title' => 'Produit']);

    $enTitle = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->titleField->id)
        ->where('locale', 'en')
        ->first();
    expect($enTitle->val_text)->toBe('Product');

    $frTitle = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->titleField->id)
        ->where('locale', 'fr')
        ->first();
    expect($frTitle->val_text)->toBe('Produit');

    $priceCount = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->priceField->id)
        ->count();
    expect($priceCount)->toBe(1);
});

it('retrieves all locales for a record', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('en')
        ->create(['title' => 'Product', 'price' => 29.99]);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('fr')
        ->update($record->id, ['title' => 'Produit']);

    $allLocales = EavQueryBuilder::for($this->collection)
        ->getAllLocaleData($record);

    expect($allLocales['title'])->toBe(['en' => 'Product', 'fr' => 'Produit'])
        ->and((float) $allLocales['price'])->toBe(29.99);
});

it('uses default locale when no locale explicitly set', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->create(['title' => 'Product', 'price' => 29.99]);

    $titleValue = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->titleField->id)
        ->first();

    expect($titleValue->locale)->toBe('en');
});

it('works without multilingual enabled', function () {
    config(['filament-studio.locales.enabled' => false]);

    $collection = StudioCollection::factory()->create([
        'tenant_id' => 1,
        'supported_locales' => null,
        'default_locale' => null,
    ]);

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'tenant_id' => 1,
        'column_name' => 'name',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_translatable' => false,
    ]);

    $record = EavQueryBuilder::for($collection)
        ->tenant(1)
        ->create(['name' => 'Test']);

    $data = EavQueryBuilder::for($collection)->getRecordData($record);

    expect($data['name'])->toBe('Test');
});

it('updates translatable and non-translatable fields together', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('en')
        ->create(['title' => 'Product', 'price' => 29.99]);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('fr')
        ->update($record->id, ['title' => 'Produit', 'price' => 39.99]);

    // FR title should be stored, price should update the single (en locale) value
    $frData = EavQueryBuilder::for($this->collection)
        ->locale('fr')
        ->getRecordData($record);

    expect($frData['title'])->toBe('Produit')
        ->and((float) $frData['price'])->toBe(39.99);

    // EN title should remain unchanged
    $enData = EavQueryBuilder::for($this->collection)
        ->locale('en')
        ->getRecordData($record);

    expect($enData['title'])->toBe('Product')
        ->and((float) $enData['price'])->toBe(39.99); // price is non-translatable, same value
});

it('handles multiple translatable fields independently', function () {
    $descField = StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'tenant_id' => 1,
        'column_name' => 'description',
        'label' => 'Description',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_translatable' => true,
    ]);

    EavQueryBuilder::invalidateFieldCache($this->collection->id);

    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('en')
        ->create(['title' => 'Product', 'description' => 'A great product', 'price' => 29.99]);

    // Translate only title to FR, not description
    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('fr')
        ->update($record->id, ['title' => 'Produit']);

    $result = EavQueryBuilder::for($this->collection)
        ->locale('fr')
        ->getRecordDataWithMeta($record);

    expect($result['data']['title'])->toBe('Produit')
        ->and($result['data']['description'])->toBe('A great product')
        ->and($result['fallbacks'])->toContain('description')
        ->and($result['fallbacks'])->not->toContain('title');
});

it('stores non-translatable field values in default locale only', function () {
    // Create with FR locale
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('fr')
        ->create(['title' => 'Produit', 'price' => 29.99]);

    // Price should be stored with 'en' (default locale), not 'fr'
    $priceValues = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->priceField->id)
        ->get();

    expect($priceValues)->toHaveCount(1)
        ->and($priceValues->first()->locale)->toBe('en');
});

it('returns single locale map for translatable field with only one locale', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('en')
        ->create(['title' => 'Product', 'price' => 29.99]);

    $allLocales = EavQueryBuilder::for($this->collection)
        ->getAllLocaleData($record);

    // Only EN exists for title, no FR was added
    expect($allLocales['title'])->toBe(['en' => 'Product'])
        ->and((float) $allLocales['price'])->toBe(29.99);
});

it('returns empty fallbacks when all translatable fields have active locale', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('en')
        ->create(['title' => 'Product', 'price' => 29.99]);

    $result = EavQueryBuilder::for($this->collection)
        ->locale('en')
        ->getRecordDataWithMeta($record);

    expect($result['fallbacks'])->toBeEmpty();
});

it('overwrites existing locale value on update', function () {
    $record = EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('en')
        ->create(['title' => 'Product', 'price' => 29.99]);

    EavQueryBuilder::for($this->collection)
        ->tenant(1)
        ->locale('en')
        ->update($record->id, ['title' => 'Updated Product']);

    // Should still be one EN value, not two
    $titleValues = StudioValue::where('record_id', $record->id)
        ->where('field_id', $this->titleField->id)
        ->where('locale', 'en')
        ->get();

    expect($titleValues)->toHaveCount(1)
        ->and($titleValues->first()->val_text)->toBe('Updated Product');
});
