<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Services\AssertCanEnablePublicWebhook;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\Permission\Models\Role;

it('throws AuthorizationException when non-admin tries to enable none mode', function () {
    $editor = $this->makeUserWith(['publish_flow']);
    $flow = StudioFlow::factory()->create(['webhook_auth_mode' => 'hmac']);

    $service = app(AssertCanEnablePublicWebhook::class);

    expect(fn () => $service->assert($flow, $editor))
        ->toThrow(AuthorizationException::class);
});

it('allows studio-admin to enable none mode', function () {
    Role::firstOrCreate(['name' => 'studio-admin']);
    $admin = $this->makeUserWith(['publish_flow', 'run_dangerous_operations']);
    $admin->assignRole('studio-admin');
    $flow = StudioFlow::factory()->create(['webhook_auth_mode' => 'hmac']);

    $service = app(AssertCanEnablePublicWebhook::class);

    // Should not throw
    $service->assert($flow, $admin);
    expect(true)->toBeTrue();
});
