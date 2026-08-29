<?php

declare(strict_types=1);

use Carbon\Carbon;
use Flexpik\FilamentStudio\Flows\Commands\DispatchScheduledFlowsCommand;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Jobs\ExecuteFlowJob;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;

beforeEach(function () {
    $this->app->make(Kernel::class)
        ->registerCommand(app(DispatchScheduledFlowsCommand::class));

    $this->flow = StudioFlow::factory()->create(['status' => FlowStatus::Active]);
    $version = StudioFlowVersion::factory()->for($this->flow, 'flow')->published()->create([
        'graph' => ['nodes' => [['id' => 'trigger', 'type' => 'trigger', 'data' => [
            'triggerType' => 'schedule',
            'config' => ['cron' => '* * * * *', 'timezone' => 'UTC'],
        ]]], 'edges' => []],
    ]);
    $this->flow->update(['published_version_id' => $version->id]);
});

it('dispatches due active scheduled flows', function () {
    Bus::fake();

    Date::setTestNow(Carbon::create(2026, 5, 10, 9, 0, 0));
    Artisan::call('studio:flows:dispatch-scheduled');

    Bus::assertDispatched(ExecuteFlowJob::class);
});

it('skips when a run is already pending or running for the flow', function () {
    Bus::fake();
    $version = $this->flow->publishedVersion;
    StudioFlowRun::factory()->for($this->flow, 'flow')->create([
        'flow_version_id' => $version->id,
        'status' => FlowRunStatus::Running,
    ]);

    Date::setTestNow(Carbon::create(2026, 5, 10, 9, 0, 0));
    Artisan::call('studio:flows:dispatch-scheduled');

    Bus::assertNotDispatched(ExecuteFlowJob::class);
});

it('does not dispatch inactive flows', function () {
    Bus::fake();
    $this->flow->forceFill(['status' => FlowStatus::Inactive])->save();

    Artisan::call('studio:flows:dispatch-scheduled');

    Bus::assertNotDispatched(ExecuteFlowJob::class);
});
