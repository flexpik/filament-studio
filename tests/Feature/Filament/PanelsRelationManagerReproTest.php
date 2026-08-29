<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Flexpik\FilamentStudio\Resources\DashboardResource\Pages\EditDashboard;
use Flexpik\FilamentStudio\Resources\DashboardResource\RelationManagers\PanelsRelationManager;
use Illuminate\Foundation\Auth\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::forceCreate(['name' => 'Test', 'email' => fake()->unique()->safeEmail(), 'password' => bcrypt('password')]);
    actingAs($this->user);
    $this->dashboard = StudioDashboard::factory()->create();
    $this->collection = StudioCollection::factory()->create(['tenant_id' => $this->dashboard->tenant_id]);
    $this->field = StudioField::factory()->create(['collection_id' => $this->collection->id, 'column_name' => 'name']);
});

it('reproduces stepwise create like a real browser interaction', function () {
    $component = Livewire::test(PanelsRelationManager::class, [
        'ownerRecord' => $this->dashboard,
        'pageClass' => EditDashboard::class,
    ]);

    $component->mountTableAction('create');

    // Step 1: user selects panel type -> triggers ->live() round trip
    $component->set('mountedActions.0.data.panel_type', 'list');

    // Step 2: user fills the dynamically-appeared Configuration fields
    $component->set('mountedActions.0.data.config.collection_id', $this->collection->id);
    $component->set('mountedActions.0.data.config.display_template', '{{name}}');
    $component->set('mountedActions.0.data.header_label', 'Repro List');

    $component->call('callMountedAction');

    $component->assertHasNoTableActionErrors();

    $panel = StudioPanel::where('dashboard_id', $this->dashboard->id)
        ->where('header_label', 'Repro List')
        ->first();

    expect($panel)->not->toBeNull();
    expect($panel->config['collection_id'] ?? null)->toBe($this->collection->id);
    expect($panel->config['display_template'] ?? null)->toBe('{{name}}');
});
