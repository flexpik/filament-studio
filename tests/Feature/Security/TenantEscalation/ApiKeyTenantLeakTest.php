<?php

// tests/Feature/Security/TenantEscalation/ApiKeyTenantLeakTest.php

use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;

it('does not return API keys belonging to another tenant', function () {
    $keyA = StudioApiKey::factory()->forTenant(1)->create(['name' => 'Tenant A Key']);
    $keyB = StudioApiKey::factory()->forTenant(2)->create(['name' => 'Tenant B Key']);

    $visibleToTenantA = StudioApiKey::query()->forTenant(1)->get();

    expect($visibleToTenantA)->toHaveCount(1)
        ->and($visibleToTenantA->first()->id)->toBe($keyA->id);
});

it('collection dropdown in API key form only shows current tenant collections', function () {
    StudioCollection::factory()->forTenant(1)->create(['label' => 'My Collection', 'slug' => 'my-col']);
    StudioCollection::factory()->forTenant(2)->create(['label' => 'Secret Collection', 'slug' => 'secret-col']);

    $unscoped = StudioCollection::query()->pluck('label', 'slug')->toArray();
    expect($unscoped)->toHaveCount(2);

    $scoped = StudioCollection::query()->forTenant(1)->pluck('label', 'slug')->toArray();
    expect($scoped)->toHaveCount(1)
        ->and($scoped)->toHaveKey('my-col')
        ->and($scoped)->not->toHaveKey('secret-col');
});
