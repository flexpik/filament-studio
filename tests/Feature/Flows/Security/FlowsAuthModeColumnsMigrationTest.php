<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Enums\WebhookAuthMode;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Illuminate\Support\Facades\Schema;

it('adds webhook_auth_mode and allowed_api_key_ids to studio_flows', function () {
    expect(Schema::hasColumn('studio_flows', 'webhook_auth_mode'))->toBeTrue();
    expect(Schema::hasColumn('studio_flows', 'webhook_allowed_studio_api_key_ids'))->toBeTrue();
});

it('defaults webhook_auth_mode to hmac', function () {
    $flow = StudioFlow::factory()->create();

    expect($flow->webhook_auth_mode)->toBe(WebhookAuthMode::Hmac);
});
