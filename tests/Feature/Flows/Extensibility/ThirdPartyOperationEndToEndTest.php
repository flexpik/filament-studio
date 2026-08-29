<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;
use Flexpik\FilamentStudio\Tests\Fixtures\Flows\SlackPackage\SlackTestServiceProvider;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->app->register(SlackTestServiceProvider::class);
});

it('registers, publishes, and runs a third-party operation end-to-end', function () {
    Http::fake(['hooks.slack.com/*' => Http::response(['ok' => true], 200)]);

    $flow = StudioFlow::factory()->active()->create(['slug' => 'e2e-slack-test']);
    $flow->update([
        'draft_graph' => [
            'nodes' => [
                ['id' => 'trigger_1', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
                ['id' => 'slack', 'type' => 'operation', 'data' => [
                    'key' => 'slack',
                    'operationType' => 'send_slack_message',
                    'config' => [
                        'webhook_url' => 'https://hooks.slack.com/services/T/B/X',
                        'message' => 'Hello {{ $trigger.user.name }}',
                    ],
                ]],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'trigger_1', 'target' => 'slack', 'sourceHandle' => 'success'],
            ],
        ],
    ]);

    app(PublishFlowVersion::class)->publish($flow->fresh());

    $run = app(FlowDispatcher::class)->dispatchSync(
        flow: $flow->fresh(),
        triggerType: 'manual',
        payload: ['user' => ['name' => 'Ada']],
        accountability: ['user_id' => null, 'tenant_id' => null, 'role' => null, 'source' => 'manual'],
    );

    expect($run->status)->toBe(FlowRunStatus::Completed);

    $step = $run->steps()->where('operation_type', 'send_slack_message')->first();
    expect($step)->not->toBeNull();
    expect($step->output)->toMatchArray(['ok' => true]);

    Http::assertSent(fn ($request) => str_contains((string) $request->body(), 'Hello Ada'));
});

it('OperationContext::interpolate resolves trigger and named-op references', function () {
    $ctx = makeOperationContext(
        trigger: ['user' => ['name' => 'Bob']],
        outputs: ['step_1' => ['email' => 'bob@example.com']],
    );

    expect($ctx->interpolate('Hello {{ $trigger.user.name }}'))->toBe('Hello Bob');
    expect($ctx->interpolate('Email: {{ $step_1.email }}'))->toBe('Email: bob@example.com');
});
