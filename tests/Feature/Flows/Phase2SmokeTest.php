<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Mail\FlowGenericMail;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Illuminate\Support\Facades\Mail;

it('runs condition → branch → send_email when condition matches', function () {
    Mail::fake();

    $flow = StudioFlow::factory()->active()->create(['slug' => 'p2-smoke']);
    $p2Version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create([
        'graph' => [
            'nodes' => [
                ['id' => 'trigger', 'type' => 'trigger', 'data' => ['triggerType' => 'manual']],
                ['id' => 'cond', 'type' => 'operation', 'data' => [
                    'key' => 'cond',
                    'operationType' => 'condition',
                    'config' => [
                        'filter' => ['logic' => 'and', 'rules' => [
                            ['path' => '$trigger.send', 'operator' => 'equals', 'value' => true],
                        ]],
                    ],
                ]],
                ['id' => 'mail', 'type' => 'operation', 'data' => [
                    'key' => 'mail',
                    'operationType' => 'send_email',
                    'config' => [
                        'to' => '{{ $trigger.email }}',
                        'subject' => 'Hi',
                        'body' => '<p>Hi</p>',
                    ],
                ]],
                ['id' => 'log', 'type' => 'operation', 'data' => [
                    'key' => 'log',
                    'operationType' => 'log_message',
                    'config' => [
                        'level' => 'info',
                        'message' => 'skipped',
                    ],
                ]],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 'trigger', 'target' => 'cond', 'sourceHandle' => 'success'],
                ['id' => 'e2', 'source' => 'cond', 'target' => 'mail', 'sourceHandle' => 'success'],
                ['id' => 'e3', 'source' => 'cond', 'target' => 'log', 'sourceHandle' => 'failure'],
            ],
        ],
    ]);
    $flow->update(['published_version_id' => $p2Version->id]);

    $run = app(FlowDispatcher::class)->dispatchSync(
        flow: $flow,
        triggerType: 'manual',
        payload: ['send' => true, 'email' => 'a@b.com'],
        accountability: ['user_id' => null, 'tenant_id' => null, 'role' => null, 'source' => 'manual'],
    );

    expect($run->status)->toBe(FlowRunStatus::Completed);
    Mail::assertSent(FlowGenericMail::class);
    expect($run->steps()->where('operation_key', 'log')->exists())->toBeFalse();
});
