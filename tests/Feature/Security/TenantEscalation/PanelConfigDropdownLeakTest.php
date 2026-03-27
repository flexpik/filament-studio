<?php

// tests/Feature/Security/TenantEscalation/PanelConfigDropdownLeakTest.php

use Flexpik\FilamentStudio\Models\StudioCollection;

it('panel config collection dropdowns only show current tenant collections', function () {
    StudioCollection::factory()->forTenant(1)->create(['label' => 'My Data', 'slug' => 'my-data']);
    StudioCollection::factory()->forTenant(2)->create(['label' => 'Other Data', 'slug' => 'other-data']);

    $unscoped = StudioCollection::query()->pluck('label', 'id');
    expect($unscoped)->toHaveCount(2);

    $scoped = StudioCollection::query()->forTenant(1)->pluck('label', 'id');
    expect($scoped)->toHaveCount(1);
});
