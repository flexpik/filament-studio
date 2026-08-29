<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Triggers\Schedule\ScheduleTrigger;
use Flexpik\FilamentStudio\Models\StudioApiKey;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
});

it('webhook returns 409 when flow has no published version', function () {
    $flow = StudioFlow::factory()->active()->create(['slug' => 'no-publish', 'webhook_secret' => null, 'webhook_auth_mode' => 'none']);
    $r = $this->postJson("/api/studio/webhooks/{$flow->slug}", []);
    $r->assertStatus(409)->assertJson(['error' => 'no_published_version']);
});

it('manual API returns 409 when flow has no published version', function () {
    $key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => null]);
    $flow = StudioFlow::factory()->active()->create();

    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->postJson("/api/studio/flows/{$flow->id}/run")
        ->assertStatus(409)
        ->assertJson(['error' => 'no_published_version']);
});

it('schedule trigger records a failed run with refusal_reason', function () {
    $flow = StudioFlow::factory()->active()->create();

    app(ScheduleTrigger::class)->fireForFlow($flow);

    $run = StudioFlowRun::query()->where('flow_id', $flow->id)->latest()->first();
    expect($run)->not->toBeNull()
        ->and($run->status)->toBe(FlowRunStatus::Failed)
        ->and($run->accountability['refusal_reason'] ?? null)->toBe('no_published_version');
});
