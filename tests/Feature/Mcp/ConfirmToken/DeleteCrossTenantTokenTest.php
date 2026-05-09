<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenIssuer;
use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenStore;
use Flexpik\FilamentStudio\Mcp\Tools\Collections\DeleteCollectionTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;

it('rejects a confirm_token issued by another tenant', function () {
    StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 2]);

    // Issue token for tenant 1
    $tenantAToken = (new ConfirmTokenIssuer(new ConfirmTokenStore))
        ->issue('delete_collection', ['slug' => 'products'], 1)['token'];

    // Tenant 2's key tries to use tenant 1's token
    $tenantBKey = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])
        ->forTenant(2)
        ->create();

    mcpCallTool($tenantBKey, DeleteCollectionTool::class, [
        'slug' => 'products',
        'confirm_token' => $tenantAToken,
    ])->assertSee('EXPIRED_CONFIRM_TOKEN');

    // Tenant 2's collection should still exist
    expect(StudioCollection::query()->where('tenant_id', 2)->count())->toBe(1);
});
