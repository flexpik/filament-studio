<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Filament\Widgets\FlowDurationWidget;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Livewire\Livewire;

it('computes avg and p95 duration over the last 24h', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));
    $durations = [100, 100, 100, 100, 100, 100, 100, 100, 100, 5000];
    foreach ($durations as $d) {
        StudioFlowRun::factory()->create([
            'status' => 'completed',
            'started_at' => now()->subHours(1),
            'duration_ms' => $d,
            'dry_run' => false,
        ]);
    }

    $widget = new FlowDurationWidget;
    [$avg, $p95] = $widget->computeDurations();
    expect($avg)->toBe('590ms');
    expect($p95)->toBe('5,000ms');
});

it('excludes dry runs', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));
    StudioFlowRun::factory()->count(5)->create([
        'duration_ms' => 9999,
        'dry_run' => true,
        'started_at' => now()->subHours(1),
    ]);

    $widget = new FlowDurationWidget;
    [$avg, $p95] = $widget->computeDurations();
    expect($avg)->toBe('0ms');
});

it('renders the widget successfully', function () {
    $this->actingAs($this->makeUserWith(['view_flows']));

    Livewire::test(FlowDurationWidget::class)->assertOk();
});
