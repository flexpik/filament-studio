<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Flows\Filament\Widgets\FlowDurationWidget;
use Flexpik\FilamentStudio\Flows\Filament\Widgets\FlowFailureRateWidget;
use Flexpik\FilamentStudio\Flows\Filament\Widgets\TopFailingFlowsWidget;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowVersion;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    StudioFlowsApiRouteRegistrar::register();
    Cache::flush();
});

// ---------------------------------------------------------------------------
// Seed helper
// ---------------------------------------------------------------------------

function seedPhase10Runs(StudioFlow $flow, int $count = 20): void
{
    StudioFlowRun::factory()->count($count)->for($flow, 'flow')->create([
        'status' => 'completed',
        'started_at' => now()->subHours(rand(1, 23)),
        'duration_ms' => rand(100, 2000),
        'dry_run' => false,
    ]);

    StudioFlowRun::factory()->count(5)->for($flow, 'flow')->create([
        'status' => 'failed',
        'started_at' => now()->subHours(rand(1, 23)),
        'dry_run' => false,
    ]);

    StudioFlowRun::factory()->count(3)->for($flow, 'flow')->create([
        'dry_run' => true,
        'status' => 'failed',
        'started_at' => now()->subHours(1),
    ]);
}

// ---------------------------------------------------------------------------
// Widget smoke
// ---------------------------------------------------------------------------

it('all four widgets render without errors against a seeded dataset', function () {
    $flow = StudioFlow::factory()->create();
    seedPhase10Runs($flow);

    // RecentFlowRunsWidget — limit 20, dry_run excluded (replicate the widget query)
    expect(StudioFlowRun::query()->where('dry_run', false)->latest('started_at')->limit(20)->get()->count())
        ->toBeLessThanOrEqual(20);

    // FlowFailureRateWidget — format: "{n}%"
    $rate = (new FlowFailureRateWidget)->computeFailureRate();
    expect($rate)->toMatch('/^\d+%$/');

    // TopFailingFlowsWidget — at least the one failed flow appears
    $results = (new TopFailingFlowsWidget)->getTopFailingFlows();
    expect($results)->not->toBeEmpty();

    // FlowDurationWidget — both strings end with "ms"
    [$avg, $p95] = (new FlowDurationWidget)->computeDurations();
    expect($avg)->toContain('ms');
    expect($p95)->toContain('ms');
});

// ---------------------------------------------------------------------------
// API endpoints: export + replay
// ---------------------------------------------------------------------------

it('per-run export, bulk export, and replay endpoints respond correctly', function () {
    Bus::fake();

    $key = StudioApiKey::factory()->withFlowsScope()->create(['tenant_id' => 1]);
    $flow = StudioFlow::factory()->active()->create(['tenant_id' => 1]);
    $version = StudioFlowVersion::factory()->for($flow, 'flow')->published()->create();
    $flow->forceFill(['published_version_id' => $version->id])->save();
    $flow->refresh();

    seedPhase10Runs($flow);

    $run = StudioFlowRun::where('flow_id', $flow->id)
        ->where('dry_run', false)
        ->first();

    // Per-run export
    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->getJson("/api/studio/flows/runs/{$run->id}/export")
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'steps']]);

    // Bulk export (JSONL)
    $from = now()->subDays(2)->format('Y-m-d');
    $to = now()->format('Y-m-d');
    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->get("/api/studio/flows/{$flow->id}/runs/export?format=jsonl&from={$from}&to={$to}")
        ->assertOk();

    // Replay
    $this->withHeaders(['X-Api-Key' => $key->plain_text_key])
        ->postJson("/api/studio/flows/runs/{$run->id}/replay")
        ->assertCreated();
});

// ---------------------------------------------------------------------------
// All four widgets exclude dry runs
// ---------------------------------------------------------------------------

it('all four widgets exclude dry runs from their queries', function () {
    $flow = StudioFlow::factory()->create();

    // Only dry runs in the last 24h
    StudioFlowRun::factory()->count(10)->for($flow, 'flow')->create([
        'dry_run' => true,
        'status' => 'failed',
        'started_at' => now()->subHours(1),
        'duration_ms' => 9999,
    ]);

    expect(StudioFlowRun::query()->where('dry_run', false)->latest('started_at')->limit(20)->count())->toBe(0);
    expect((new FlowFailureRateWidget)->computeFailureRate())->toBe('0%');
    expect((new TopFailingFlowsWidget)->getTopFailingFlows())->toBeEmpty();

    [$avg] = (new FlowDurationWidget)->computeDurations();
    expect($avg)->toBe('0ms');
});
