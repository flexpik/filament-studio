<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Filament\Widgets\FlowFailureRateWidget;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Livewire\Livewire;

it('computes failure rate over the last 24h', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));
    // 10 runs in last 24h, 3 failed
    StudioFlowRun::factory()->count(7)->create(['status' => 'completed', 'started_at' => now()->subHours(1), 'dry_run' => false]);
    StudioFlowRun::factory()->count(3)->create(['status' => 'failed', 'started_at' => now()->subHours(1), 'dry_run' => false]);

    Livewire::test(FlowFailureRateWidget::class)->assertOk();

    // Direct test via static method
    $widget = new FlowFailureRateWidget;
    $rate = $widget->computeFailureRate();
    expect($rate)->toBe('30%');
});

it('excludes dry runs from the rate', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));
    // 10 dry run failures, 5 real runs (0 failed)
    StudioFlowRun::factory()->count(10)->create(['status' => 'failed', 'started_at' => now()->subHours(1), 'dry_run' => true]);
    StudioFlowRun::factory()->count(5)->create(['status' => 'completed', 'started_at' => now()->subHours(1), 'dry_run' => false]);

    $widget = new FlowFailureRateWidget;
    expect($widget->computeFailureRate())->toBe('0%');
});
