<?php

use Flexpik\FilamentStudio\Api\StudioApiRouteRegistrar;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

beforeEach(function () {
    StudioApiRouteRegistrar::register();

    $this->collection = StudioCollection::factory()->create(['slug' => 'posts']);

    $this->titleField = StudioField::factory()->required()->create([
        'collection_id' => $this->collection->id,
        'column_name' => 'title',
        'label' => 'Title',
        'field_type' => 'text',
        'eav_cast' => 'text',
        'sort_order' => 1,
    ]);

    EavQueryBuilder::invalidateFieldCache($this->collection->id);

    $this->plainKey = Str::random(64);
    $this->apiKey = StudioApiKey::factory()->fullAccess()->create([
        'key' => hash('sha256', $this->plainKey),
        'last_used_at' => null,
    ]);
});

function concurrencyApiHeaders(string $key): array
{
    return [
        'X-Api-Key' => $key,
        'Accept' => 'application/json',
    ];
}

it('updates last_used_at after each sequential API request', function () {
    // Verify last_used_at starts as null
    expect($this->apiKey->fresh()->last_used_at)->toBeNull();

    // First request
    Carbon::setTestNow(Carbon::parse('2026-03-22 10:00:00'));
    $response = $this->getJson('/api/studio/posts', concurrencyApiHeaders($this->plainKey));
    $response->assertOk();

    $afterFirst = $this->apiKey->fresh()->last_used_at;
    expect($afterFirst)->not->toBeNull()
        ->and($afterFirst->toDateTimeString())->toBe('2026-03-22 10:00:00');

    // Second request at a later time
    Carbon::setTestNow(Carbon::parse('2026-03-22 10:05:00'));
    $response = $this->getJson('/api/studio/posts', concurrencyApiHeaders($this->plainKey));
    $response->assertOk();

    $afterSecond = $this->apiKey->fresh()->last_used_at;
    expect($afterSecond->toDateTimeString())->toBe('2026-03-22 10:05:00')
        ->and($afterSecond->gt($afterFirst))->toBeTrue();

    Carbon::setTestNow();
});

it('reflects the most recent timestamp after rapid sequential requests', function () {
    $timestamps = [
        '2026-03-22 12:00:00',
        '2026-03-22 12:00:01',
        '2026-03-22 12:00:02',
        '2026-03-22 12:00:03',
        '2026-03-22 12:00:04',
    ];

    foreach ($timestamps as $timestamp) {
        Carbon::setTestNow(Carbon::parse($timestamp));
        $response = $this->getJson('/api/studio/posts', concurrencyApiHeaders($this->plainKey));
        $response->assertOk();
    }

    $lastUsed = $this->apiKey->fresh()->last_used_at;
    expect($lastUsed->toDateTimeString())->toBe('2026-03-22 12:00:04');

    Carbon::setTestNow();
});

it('keeps api key state consistent across multiple requests', function () {
    $originalIsActive = $this->apiKey->is_active;
    $originalPermissions = $this->apiKey->permissions;

    // Make several requests with the same key
    for ($i = 0; $i < 5; $i++) {
        Carbon::setTestNow(Carbon::parse('2026-03-22 14:00:00')->addMinutes($i));
        $response = $this->getJson('/api/studio/posts', concurrencyApiHeaders($this->plainKey));
        $response->assertOk();

        $freshKey = $this->apiKey->fresh();
        expect($freshKey->is_active)->toBe($originalIsActive)
            ->and($freshKey->permissions)->toBe($originalPermissions);
    }

    // Verify the key still works after all requests
    Carbon::setTestNow(Carbon::parse('2026-03-22 15:00:00'));
    $response = $this->getJson('/api/studio/posts', concurrencyApiHeaders($this->plainKey));
    $response->assertOk();

    Carbon::setTestNow();
});
