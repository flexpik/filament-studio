<?php

// tests/Feature/Security/TenantEscalation/DashboardTenantLeakTest.php

use Flexpik\FilamentStudio\Models\StudioDashboard;

it('does not return dashboards belonging to another tenant', function () {
    $dashA = StudioDashboard::factory()->forTenant(1)->create(['name' => 'Tenant A Dashboard']);
    $dashB = StudioDashboard::factory()->forTenant(2)->create(['name' => 'Tenant B Dashboard']);

    $visibleToTenantA = StudioDashboard::query()->forTenant(1)->get();

    expect($visibleToTenantA)->toHaveCount(1)
        ->and($visibleToTenantA->first()->id)->toBe($dashA->id);
});

it('does not allow Tenant A to access Tenant B dashboard by direct ID', function () {
    $dashB = StudioDashboard::factory()->forTenant(2)->create();

    $result = StudioDashboard::query()->forTenant(1)->find($dashB->id);

    expect($result)->toBeNull();
});
