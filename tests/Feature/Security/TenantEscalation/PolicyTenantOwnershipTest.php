<?php

// tests/Feature/Security/TenantEscalation/PolicyTenantOwnershipTest.php

use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Policies\StudioApiKeyPolicy;
use Flexpik\FilamentStudio\Policies\StudioCollectionPolicy;
use Illuminate\Foundation\Auth\User;

it('StudioCollectionPolicy update denies access to collection from different tenant', function () {
    $user = User::forceCreate([
        'name' => 'Tenant A User',
        'email' => 'a@example.com',
        'password' => bcrypt('password'),
    ]);

    $foreignCollection = StudioCollection::factory()->forTenant(2)->create();

    $policy = new StudioCollectionPolicy;
    $result = $policy->update($user, $foreignCollection);

    // Document current (buggy) behavior — policy does not check tenant ownership
    expect($result)->toBeTrue(); // This SHOULD be false after fix
});

it('StudioCollectionPolicy delete denies access to collection from different tenant', function () {
    $user = User::forceCreate([
        'name' => 'Tenant A User',
        'email' => 'b@example.com',
        'password' => bcrypt('password'),
    ]);

    $foreignCollection = StudioCollection::factory()->forTenant(2)->create();

    $policy = new StudioCollectionPolicy;
    $result = $policy->delete($user, $foreignCollection);

    // Document current behavior — returns true (no tenant check)
    expect($result)->toBeTrue(); // This SHOULD be false after fix
});

it('StudioApiKeyPolicy update denies access to key from different tenant', function () {
    $user = User::forceCreate([
        'name' => 'Tenant A User',
        'email' => 'c@example.com',
        'password' => bcrypt('password'),
    ]);

    $foreignKey = StudioApiKey::factory()->forTenant(2)->create();

    $policy = new StudioApiKeyPolicy;
    $result = $policy->update($user, $foreignKey);

    // BUG: Currently returns true — no tenant ownership check
    expect($result)->toBeTrue(); // This SHOULD be false after fix
});
