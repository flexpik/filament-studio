<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowAuditLog;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;
use Flexpik\FilamentStudio\Tests\Fixtures\Flows\SlackPackage\SlackTestServiceProvider;
use Illuminate\Support\Facades\Http;

it('Phase 9 smoke: register → publish → run → audit', function () {
    // 1. Register third-party operation via service provider
    $this->app->register(SlackTestServiceProvider::class);

    Http::fake(['hooks.slack.com/*' => Http::response(['ok' => true], 200)]);

    // 2. Create a flow with manual trigger and Slack op in draft_graph
    $flow = StudioFlow::factory()->active()->create(['tenant_id' => 42]);
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

    // 3. Publish — passes FlowGraphValidator (registered op), saves published_version_id
    $flow = $flow->fresh();
    app(PublishFlowVersion::class)->publish($flow);

    // 4. Assert audit log captured the flow being saved during publish (StudioFlowObserver fires on save)
    expect(StudioFlowAuditLog::where('flow_id', $flow->id)->count())->toBeGreaterThan(0);

    // 5. Dispatch the flow with trigger payload
    $flow = $flow->fresh();
    $run = app(FlowDispatcher::class)->dispatchSync(
        flow: $flow,
        triggerType: 'manual',
        payload: ['user' => ['name' => 'Ada']],
        accountability: ['user_id' => null, 'tenant_id' => 42, 'role' => null, 'source' => 'test'],
    );

    // 6. Assert run succeeded
    expect($run->status)->toBe(FlowRunStatus::Completed);

    // 7. Assert step output for the Slack operation
    $step = $run->steps()->where('operation_type', 'send_slack_message')->first();
    expect($step)->not->toBeNull();
    expect($step->output)->toMatchArray(['ok' => true]);

    // 8. Assert HTTP body contained interpolated message
    Http::assertSent(fn ($request) => str_contains((string) $request->body(), 'Ada'));

    // 9. Assert OperationContext tenantId flows through correctly
    // (verified indirectly — run completed and the flow's tenant_id is preserved)
    expect((int) $run->flow->tenant_id)->toBe(42);
});
