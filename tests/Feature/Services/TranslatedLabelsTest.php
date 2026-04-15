<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;

beforeEach(function () {
    config(['filament-studio.locales.enabled' => true]);
    config(['filament-studio.locales.available' => ['en', 'fr']]);
    config(['filament-studio.locales.default' => 'en']);
});

it('resolves translated field label from translations JSON', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => ['en', 'fr'],
        'default_locale' => 'en',
    ]);

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'translations' => [
            'label' => ['en' => 'Title', 'fr' => 'Titre'],
            'placeholder' => ['en' => 'Enter title', 'fr' => 'Entrez le titre'],
        ],
    ]);

    session(['studio_locale' => 'fr']);

    expect($field->getTranslatedAttribute('label'))->toBe('Titre')
        ->and($field->getTranslatedAttribute('placeholder'))->toBe('Entrez le titre');
});

it('falls back to base attribute when no translation exists', function () {
    $field = StudioField::factory()->create([
        'collection_id' => StudioCollection::factory()->create()->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'translations' => null,
    ]);

    expect($field->getTranslatedAttribute('label'))->toBe('Title');
});

it('falls back to base attribute for unknown locale', function () {
    config(['filament-studio.locales.available' => ['en', 'fr', 'de']]);

    $collection = StudioCollection::factory()->create([
        'supported_locales' => ['en', 'fr', 'de'],
    ]);

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'translations' => [
            'label' => ['en' => 'Title', 'fr' => 'Titre'],
        ],
    ]);

    session(['studio_locale' => 'de']);

    // German not in translations, falls back to base attribute
    expect($field->getTranslatedAttribute('label'))->toBe('Title');
});

it('returns base attribute when multilingual disabled', function () {
    config(['filament-studio.locales.enabled' => false]);

    $field = StudioField::factory()->create([
        'collection_id' => StudioCollection::factory()->create()->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'translations' => [
            'label' => ['fr' => 'Titre'],
        ],
    ]);

    session(['studio_locale' => 'fr']);

    expect($field->getTranslatedAttribute('label'))->toBe('Title');
});

it('falls back to base attribute when translations is empty array', function () {
    $field = StudioField::factory()->create([
        'collection_id' => StudioCollection::factory()->create([
            'supported_locales' => ['en', 'fr'],
        ])->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'translations' => [],
    ]);

    session(['studio_locale' => 'fr']);

    expect($field->getTranslatedAttribute('label'))->toBe('Title');
});

it('falls back when attribute key exists but specific locale is missing', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => ['en', 'fr', 'de'],
        'default_locale' => 'en',
    ]);

    config(['filament-studio.locales.available' => ['en', 'fr', 'de']]);

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'translations' => [
            'label' => ['en' => 'Title', 'fr' => 'Titre'],
            // 'de' is missing from label translations
        ],
    ]);

    session(['studio_locale' => 'de']);

    expect($field->getTranslatedAttribute('label'))->toBe('Title');
});

it('resolves translated hint attribute', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => ['en', 'fr'],
        'default_locale' => 'en',
    ]);

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'label' => 'Title',
        'hint' => 'Enter a title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'translations' => [
            'label' => ['en' => 'Title', 'fr' => 'Titre'],
            'hint' => ['en' => 'Enter a title', 'fr' => 'Entrez un titre'],
        ],
    ]);

    session(['studio_locale' => 'fr']);

    expect($field->getTranslatedAttribute('hint'))->toBe('Entrez un titre');
});

it('returns base attribute for untranslated attribute key', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => ['en', 'fr'],
        'default_locale' => 'en',
    ]);

    $field = StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'label' => 'Title',
        'placeholder' => 'Enter title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'translations' => [
            'label' => ['en' => 'Title', 'fr' => 'Titre'],
            // placeholder not in translations
        ],
    ]);

    session(['studio_locale' => 'fr']);

    expect($field->getTranslatedAttribute('placeholder'))->toBe('Enter title');
});
