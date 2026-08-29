<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Filament\Widgets\TopFailingFlowsWidget;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Livewire\Livewire;

it('ranks flows by failure count in the last 24h', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));
    $flowA = StudioFlow::factory()->create();
    $flowB = StudioFlow::factory()->create();

    StudioFlowRun::factory()->count(5)->for($flowA, 'flow')->create(['status' => 'failed', 'started_at' => now()->subHours(1), 'dry_run' => false]);
    StudioFlowRun::factory()->count(2)->for($flowB, 'flow')->create(['status' => 'failed', 'started_at' => now()->subHours(1), 'dry_run' => false]);

    Livewire::test(TopFailingFlowsWidget::class)->assertOk();

    // Verify query result directly
    $results = (new TopFailingFlowsWidget)->getTopFailingFlows();
    expect($results->first()->slug)->toBe($flowA->slug);
    expect($results->first()->failure_count)->toBe(5);
});

it('excludes dry runs', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));
    $flow = StudioFlow::factory()->create();
    StudioFlowRun::factory()->count(10)->for($flow, 'flow')->create(['status' => 'failed', 'started_at' => now()->subHours(1), 'dry_run' => true]);

    $results = (new TopFailingFlowsWidget)->getTopFailingFlows();
    expect($results)->toBeEmpty();
});

it('computes failure_count in a subquery so the outer select has no GROUP BY (ONLY_FULL_GROUP_BY safe)', function () {
    $flowsTable = config('filament-studio.table_prefix', 'studio_').'flows';

    $query = (new ReflectionMethod(TopFailingFlowsWidget::class, 'topFailingFlowsQuery'))
        ->invoke(new TopFailingFlowsWidget);
    // Strip identifier quoting so the assertion is grammar-agnostic (MySQL `, SQLite ").
    $sql = preg_replace('/[`"]/', '', strtolower($query->toSql()));

    // The count is aggregated inside a joined subquery...
    expect($sql)->toContain('inner join (select');
    expect($sql)->toContain('group by flow_id');
    // ...and the outer flows select is never grouped (that is what MySQL rejects).
    expect($sql)->not->toContain('group by '.strtolower($flowsTable));
});
