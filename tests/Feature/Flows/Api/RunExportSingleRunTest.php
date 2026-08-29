<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRunStep;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Models\StudioApiKey;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();

    $this->key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => 1]);
    $this->flow = StudioFlow::factory()->active()->create(['tenant_id' => 1]);
    $version = StudioFlowVersion::factory()->for($this->flow, 'flow')->published()->create();
    $this->flow->forceFill(['published_version_id' => $version->id])->save();
    $this->flow->refresh();

    $this->run = StudioFlowRun::factory()->for($this->flow, 'flow')->create([
        'flow_version_id' => $version->id,
        'trigger_type' => 'manual',
        'trigger_payload' => ['user_id' => 42, 'password' => 'hunter2'],
    ]);

    $this->step = StudioFlowRunStep::factory()->create([
        'flow_run_id' => $this->run->id,
        'operation_key' => 'send_email',
        'operation_type' => 'email',
        'attempt_number' => 1,
        'status' => FlowRunStepStatus::Completed,
        'input' => ['to' => 'user@example.com', 'secret' => 'my-secret-value'],
        'output' => ['sent' => true],
    ]);
});

it('returns run metadata + step detail as JSON', function () {
    $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->getJson("/api/studio/flows/runs/{$this->run->id}/export")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id', 'flow_id', 'flow_version_id', 'status', 'started_at', 'finished_at',
                'duration_ms', 'trigger_type', 'trigger_payload',
                'steps' => [
                    ['id', 'operation_key', 'operation_type', 'attempt_number',
                        'status', 'input', 'output', 'error_class', 'error_message',
                        'started_at', 'finished_at'],
                ],
            ],
        ]);
});

it('masks sensitive values in trigger_payload and step I/O', function () {
    $body = $this->withHeaders(['X-Api-Key' => $this->key->plain_text_key])
        ->getJson("/api/studio/flows/runs/{$this->run->id}/export")
        ->json();

    $encoded = json_encode($body);

    expect($encoded)->not->toContain('hunter2');
    expect($encoded)->not->toContain('my-secret-value');
    expect($encoded)->toContain('***');
});

it('returns 404 for a run from another tenant', function () {
    $otherKey = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => 2]);

    $this->withHeaders(['X-Api-Key' => $otherKey->plain_text_key])
        ->getJson("/api/studio/flows/runs/{$this->run->id}/export")
        ->assertNotFound();
});

it('returns 401 for invalid API key', function () {
    $this->withHeaders(['X-Api-Key' => 'invalid-key'])
        ->getJson("/api/studio/flows/runs/{$this->run->id}/export")
        ->assertStatus(401);
});
