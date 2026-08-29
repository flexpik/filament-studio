<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowDispatcher;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Jobs\ExecuteFlowJob;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Flows\Security\HmacVerifier;
use Flexpik\FilamentStudio\Flows\Services\PublishFlowVersion;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;

it('manual trigger: dispatchSync runs immediately', function () {
    $flow = StudioFlow::factory()->active()->create();
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create();
    $flow->update(['published_version_id' => $version->id]);

    $run = app(FlowDispatcher::class)->dispatchSync($flow, 'manual', [], []);
    expect($run->status)->toBe(FlowRunStatus::Completed);
});

it('webhook trigger: register stores secret, HmacVerifier validates', function () {
    $flow = StudioFlow::factory()->create([
        'draft_graph' => ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => [
            'triggerType' => 'webhook', 'config' => ['auth_mode' => 'hmac'],
        ]]], 'edges' => []],
    ]);
    app(PublishFlowVersion::class)->publish($flow);

    $secret = $flow->fresh()->webhook_secret;
    $body = '{"x":1}';
    $sig = 'sha256='.hash_hmac('sha256', $body, $secret);

    expect((new HmacVerifier)->verify($body, $sig, $secret))->toBeTrue();
});

it('collection event trigger: creating a record dispatches the job', function () {
    Bus::fake();
    $collection = StudioCollection::factory()->create(['slug' => 'p3']);
    $flow = StudioFlow::factory()->create([
        'status' => FlowStatus::Active,
        'draft_graph' => ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => [
            'triggerType' => 'collection_event', 'config' => ['collection' => 'p3', 'events' => ['created']],
        ]]], 'edges' => []],
    ]);
    app(PublishFlowVersion::class)->publish($flow);

    StudioRecord::factory()->for($collection, 'collection')->create();

    Bus::assertDispatched(ExecuteFlowJob::class);
});

it('schedule trigger: due flow gets dispatched by command', function () {
    Bus::fake();
    $flow = StudioFlow::factory()->create([
        'status' => FlowStatus::Active,
        'draft_graph' => ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => [
            'triggerType' => 'schedule', 'config' => ['cron' => '* * * * *', 'timezone' => 'UTC'],
        ]]], 'edges' => []],
    ]);
    app(PublishFlowVersion::class)->publish($flow);

    Artisan::call('studio:flows:dispatch-scheduled');

    Bus::assertDispatched(ExecuteFlowJob::class);
});
