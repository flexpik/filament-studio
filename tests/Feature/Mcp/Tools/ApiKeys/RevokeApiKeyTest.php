<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Tools\ApiKeys\RevokeApiKeyTool;
use Flexpik\FilamentStudio\Models\StudioApiKey;

it('revokes an api key (sets is_active=false)', function () {
    $target = StudioApiKey::factory()->forTenant(1)->create(['is_active' => true]);
    $caller = StudioApiKey::factory()
        ->withPermissions(['_studio' => ['manage_api_keys']])->forTenant(1)->create();

    mcpCallTool($caller, RevokeApiKeyTool::class, ['id' => $target->id])
        ->assertSee('"revoked"')->assertSee((string) $target->id);

    expect($target->fresh()->is_active)->toBeFalse();
});
