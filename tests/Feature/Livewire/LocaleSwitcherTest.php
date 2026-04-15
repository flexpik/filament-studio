<?php

use Flexpik\FilamentStudio\Livewire\LocaleSwitcher;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Livewire\Livewire;

beforeEach(function () {
    config(['filament-studio.locales.enabled' => true]);
    config(['filament-studio.locales.available' => ['en', 'fr', 'de']]);
    config(['filament-studio.locales.default' => 'en']);
});

it('renders with available locales', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => ['en', 'fr'],
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_translatable' => true,
    ]);

    Livewire::test(LocaleSwitcher::class, ['collectionId' => $collection->id])
        ->assertSee('EN')
        ->assertSee('FR')
        ->assertDontSee('DE');
});

it('switches locale and stores in session', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => ['en', 'fr'],
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_translatable' => true,
    ]);

    Livewire::test(LocaleSwitcher::class, ['collectionId' => $collection->id])
        ->call('switchLocale', 'fr')
        ->assertSet('activeLocale', 'fr');

    expect(session('studio_locale'))->toBe('fr');
});

it('defaults to collection default locale', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => ['en', 'fr'],
        'default_locale' => 'fr',
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_translatable' => true,
    ]);

    Livewire::test(LocaleSwitcher::class, ['collectionId' => $collection->id])
        ->assertSet('activeLocale', 'fr');
});

it('is hidden when multilingual is disabled', function () {
    config(['filament-studio.locales.enabled' => false]);

    $collection = StudioCollection::factory()->create();

    Livewire::test(LocaleSwitcher::class, ['collectionId' => $collection->id])
        ->assertSet('visible', false);
});

it('is hidden when collection has no translatable fields', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => ['en', 'fr'],
    ]);

    // No translatable fields
    Livewire::test(LocaleSwitcher::class, ['collectionId' => $collection->id])
        ->assertSet('visible', false);
});

it('ignores switch to invalid locale', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => ['en', 'fr'],
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_translatable' => true,
    ]);

    Livewire::test(LocaleSwitcher::class, ['collectionId' => $collection->id])
        ->call('switchLocale', 'de')
        ->assertSet('activeLocale', 'en') // Should remain unchanged
        ->assertNotDispatched('locale-switched');
});

it('dispatches locale-switched event on valid switch', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => ['en', 'fr'],
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_translatable' => true,
    ]);

    Livewire::test(LocaleSwitcher::class, ['collectionId' => $collection->id])
        ->call('switchLocale', 'fr')
        ->assertDispatched('locale-switched');
});

it('stays hidden when collection does not exist', function () {
    Livewire::test(LocaleSwitcher::class, ['collectionId' => 99999])
        ->assertSet('visible', false)
        ->assertSet('locales', []);
});

it('is hidden when collection has supported locales but only non-translatable fields', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => ['en', 'fr'],
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'price',
        'field_type' => 'decimal',
        'eav_cast' => 'decimal',
        'is_translatable' => false,
    ]);

    Livewire::test(LocaleSwitcher::class, ['collectionId' => $collection->id])
        ->assertSet('visible', false);
});

it('is hidden when collection has null supported_locales', function () {
    $collection = StudioCollection::factory()->create([
        'supported_locales' => null,
    ]);

    StudioField::factory()->create([
        'collection_id' => $collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'is_translatable' => true,
    ]);

    Livewire::test(LocaleSwitcher::class, ['collectionId' => $collection->id])
        ->assertSet('visible', false);
});
