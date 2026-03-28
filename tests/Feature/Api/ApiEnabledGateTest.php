<?php

use Flexpik\FilamentStudio\Api\StudioApiRouteRegistrar;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Illuminate\Support\Str;

beforeEach(function () {
    StudioApiRouteRegistrar::register();

    $this->collection = StudioCollection::factory()->create([
        'api_enabled' => false,
        'slug' => 'products',
    ]);

    StudioField::factory()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'title',
        'field_type' => 'text',
        'eav_cast' => 'text',
    ]);

    $this->plainKey = Str::random(64);
    StudioApiKey::factory()->fullAccess()->create([
        'key' => hash('sha256', $this->plainKey),
    ]);
});

it('returns 404 when collection has api_enabled = false', function () {
    $prefix = config('filament-studio.api.prefix', 'api/studio');

    $this->getJson("/{$prefix}/products", [
        'X-Api-Key' => $this->plainKey,
    ])->assertNotFound();
});

it('returns 200 when collection has api_enabled = true', function () {
    $this->collection->update(['api_enabled' => true]);
    $prefix = config('filament-studio.api.prefix', 'api/studio');

    $this->getJson("/{$prefix}/products", [
        'X-Api-Key' => $this->plainKey,
    ])->assertOk();
});
