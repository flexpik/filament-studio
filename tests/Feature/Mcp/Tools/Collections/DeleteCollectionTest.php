<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenIssuer;
use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenStore;
use Flexpik\FilamentStudio\Mcp\Tools\Collections\DeleteCollectionTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;

it('deletes when given a valid confirm_token', function () {
    StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])
        ->forTenant(1)
        ->create();

    $token = (new ConfirmTokenIssuer(new ConfirmTokenStore))
        ->issue('delete_collection', ['slug' => 'products'], 1)['token'];

    mcpCallTool($key, DeleteCollectionTool::class, [
        'slug' => 'products',
        'confirm_token' => $token,
    ])->assertSee('"deleted"');

    expect(StudioCollection::count())->toBe(0);
});

it('rejects a missing or expired token', function () {
    StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])
        ->forTenant(1)
        ->create();

    mcpCallTool($key, DeleteCollectionTool::class, [
        'slug' => 'products',
        'confirm_token' => 'ct_doesnotexist',
    ])->assertSee('EXPIRED_CONFIRM_TOKEN');

    expect(StudioCollection::count())->toBe(1);
});
