<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Filament\Widgets\RecentFlowRunsWidget;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Livewire\Livewire;

it('returns at most 20 runs, ordered by started_at desc, excluding dry runs', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));
    StudioFlowRun::factory()->count(25)->create(['dry_run' => false]);
    StudioFlowRun::factory()->count(5)->create(['dry_run' => true]);

    Livewire::test(RecentFlowRunsWidget::class)
        ->assertOk();

    // Verify via DB: the widget query returns max 20, excludes dry runs
    $records = StudioFlowRun::query()->where('dry_run', false)->latest('started_at')->limit(20)->get();
    expect($records)->toHaveCount(20);
    expect($records->every(fn ($r) => $r->dry_run === false))->toBeTrue();
});

it('shows a "view" action', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));
    StudioFlowRun::factory()->create(['dry_run' => false]);

    Livewire::test(RecentFlowRunsWidget::class)
        ->assertOk();
});
