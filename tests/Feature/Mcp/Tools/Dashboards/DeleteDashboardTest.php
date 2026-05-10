<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenIssuer;
use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenStore;
use Flexpik\FilamentStudio\Mcp\Tools\Dashboards\DeleteDashboardTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioDashboard;

it('deletes dashboard with valid confirm_token', function () {
    $dash = StudioDashboard::factory()->create(['tenant_id' => 1, 'slug' => 'sales']);
    $token = (new ConfirmTokenIssuer(new ConfirmTokenStore))->issue('delete_dashboard', ['slug' => 'sales'], 1);

    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_dashboards']])->forTenant(1)->create();

    mcpCallTool($key, DeleteDashboardTool::class, [
        'slug' => 'sales',
        'confirm_token' => $token['token'],
    ])->assertSee('"deleted"')->assertSee('sales');

    expect(StudioDashboard::find($dash->id))->toBeNull();
});

it('rejects delete with bogus token', function () {
    StudioDashboard::factory()->create(['tenant_id' => 1, 'slug' => 'sales']);

    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_dashboards']])->forTenant(1)->create();

    mcpCallTool($key, DeleteDashboardTool::class, [
        'slug' => 'sales',
        'confirm_token' => 'ct_bogus',
    ])->assertSee('EXPIRED_CONFIRM_TOKEN');
});
