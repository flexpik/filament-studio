<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenIssuer;
use Flexpik\FilamentStudio\Mcp\ConfirmTokens\ConfirmTokenStore;
use Flexpik\FilamentStudio\Mcp\Tools\Fields\DeleteFieldTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;

it('deletes a field with valid confirm_token', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($c, 'collection')->create(['column_name' => 'sku', 'tenant_id' => 1]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])
        ->forTenant(1)
        ->create();
    $token = (new ConfirmTokenIssuer(new ConfirmTokenStore))
        ->issue('delete_field', ['collection_slug' => 'products', 'column_name' => 'sku'], 1)['token'];

    mcpCallTool($key, DeleteFieldTool::class, [
        'collection_slug' => 'products',
        'column_name' => 'sku',
        'confirm_token' => $token,
    ])->assertSee('"deleted"');

    expect(StudioField::count())->toBe(0);
});

it('rejects expired or invalid token', function () {
    $c = StudioCollection::factory()->create(['slug' => 'products', 'tenant_id' => 1]);
    StudioField::factory()->for($c, 'collection')->create(['column_name' => 'sku', 'tenant_id' => 1]);
    $key = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_collections']])
        ->forTenant(1)
        ->create();

    mcpCallTool($key, DeleteFieldTool::class, [
        'collection_slug' => 'products',
        'column_name' => 'sku',
        'confirm_token' => 'ct_doesnotexist',
    ])->assertSee('EXPIRED_CONFIRM_TOKEN');

    expect(StudioField::count())->toBe(1);
});
