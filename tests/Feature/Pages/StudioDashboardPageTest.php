<?php

use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves panels for a dashboard', function () {
    $dashboard = StudioDashboard::factory()->create(['tenant_id' => 1]);

    StudioPanel::factory()->count(3)->create([
        'dashboard_id' => $dashboard->id,
        'tenant_id' => 1,
        'placement' => PanelPlacement::Dashboard,
        'panel_type' => 'label',
        'config' => ['text' => 'Test'],
    ]);

    $panels = StudioPanel::query()
        ->forDashboard($dashboard->id)
        ->get();

    expect($panels)->toHaveCount(3);
});
