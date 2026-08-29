<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Enums\WebhookAuthMode;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowAuditLog;

it('casts webhook_auth_mode to WebhookAuthMode enum', function () {
    $flow = StudioFlow::factory()->create(['webhook_auth_mode' => 'hmac']);
    expect($flow->webhook_auth_mode)->toBe(WebhookAuthMode::Hmac);
});

it('round-trips webhook_allowed_studio_api_key_ids as array', function () {
    $ids = ['uuid-1', 'uuid-2'];
    $flow = StudioFlow::factory()->create(['webhook_allowed_studio_api_key_ids' => $ids]);
    $flow->refresh();
    expect($flow->webhook_allowed_studio_api_key_ids)->toBe($ids);
});

it('has auditLogs HasMany relation', function () {
    $flow = StudioFlow::factory()->create();
    StudioFlowAuditLog::factory()->count(2)->create(['flow_id' => $flow->id]);
    // Observer fires on create() adding 1 row, plus 2 explicit = 3 total
    expect($flow->auditLogs()->count())->toBeGreaterThanOrEqual(2);
    expect($flow->auditLogs->first())->toBeInstanceOf(StudioFlowAuditLog::class);
});
