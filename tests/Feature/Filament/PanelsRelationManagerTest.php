<?php

use Flexpik\FilamentStudio\Enums\PanelPlacement;
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
    $this->field = StudioField::factory()->create(['collection_id' => $this->collection->id, 'column_name' => 'amount']);
});

it('can render the panels relation manager', function () {
    Livewire::test(PanelsRelationManager::class, [
        'ownerRecord' => $this->dashboard,
        'pageClass' => EditDashboard::class,
    ])
        ->assertSuccessful();
});

it('can list panels for a dashboard', function () {
    $panels = StudioPanel::factory()
        ->count(2)
        ->sequence(
            ['header_label' => 'Panel A', 'grid_order' => 1],
            ['header_label' => 'Panel B', 'grid_order' => 2],
        )
        ->forDashboard($this->dashboard)
        ->create();

    Livewire::test(PanelsRelationManager::class, [
        'ownerRecord' => $this->dashboard,
        'pageClass' => EditDashboard::class,
    ])
        ->assertCanSeeTableRecords($panels);
});

it('displays expected panel columns', function () {
    StudioPanel::factory()->forDashboard($this->dashboard)->create();

    Livewire::test(PanelsRelationManager::class, [
        'ownerRecord' => $this->dashboard,
        'pageClass' => EditDashboard::class,
    ])
        ->assertCanRenderTableColumn('panel_type')
        ->assertCanRenderTableColumn('header_label')
        ->assertCanRenderTableColumn('grid_col_span')
        ->assertCanRenderTableColumn('grid_order');
});

it('can edit a panel via the relation manager', function () {
    $panel = StudioPanel::factory()->forDashboard($this->dashboard)->create([
        'panel_type' => 'metric',
        'header_label' => 'Original',
        'header_visible' => true,
        'grid_col_span' => 6,
        'grid_row_span' => 4,
        'config' => [
            'collection_id' => $this->collection->id,
            'field' => $this->field->column_name,
            'aggregate_function' => 'count',
        ],
    ]);

    Livewire::test(PanelsRelationManager::class, [
        'ownerRecord' => $this->dashboard,
        'pageClass' => EditDashboard::class,
    ])
        ->callTableAction('edit', $panel, data: [
            'header_label' => 'Updated Label',
            'grid_col_span' => 4,
        ])
        ->assertHasNoTableActionErrors();

    $panel->refresh();
    expect($panel->header_label)->toBe('Updated Label')
        ->and($panel->grid_col_span)->toBe(4);
});

it('can create a panel via the relation manager', function () {
    Livewire::test(PanelsRelationManager::class, [
        'ownerRecord' => $this->dashboard,
        'pageClass' => EditDashboard::class,
    ])
        ->callTableAction('create', data: [
            'panel_type' => 'metric',
            'header_visible' => true,
            'header_label' => 'New Metric',
            'grid_col_span' => 4,
            'grid_row_span' => 3,
            'config' => [
                'collection_id' => $this->collection->id,
                'field' => $this->field->column_name,
                'aggregate_function' => 'count',
            ],
        ])
        ->assertHasNoTableActionErrors();

    $panel = StudioPanel::where('dashboard_id', $this->dashboard->id)
        ->where('header_label', 'New Metric')
        ->first();

    expect($panel)
        ->not->toBeNull()
        ->panel_type->toBe('metric')
        ->grid_col_span->toBe(4)
        ->grid_row_span->toBe(3)
        ->placement->toBe(PanelPlacement::Dashboard);
});

it('sets tenant_id and dashboard_id when creating a panel', function () {
    Livewire::test(PanelsRelationManager::class, [
        'ownerRecord' => $this->dashboard,
        'pageClass' => EditDashboard::class,
    ])
        ->callTableAction('create', data: [
            'panel_type' => 'label',
            'header_visible' => true,
            'header_label' => 'Info Label',
            'grid_col_span' => 6,
            'grid_row_span' => 2,
            'config' => [
                'text' => 'Some label text',
            ],
        ])
        ->assertHasNoTableActionErrors();

    $panel = StudioPanel::where('dashboard_id', $this->dashboard->id)
        ->where('header_label', 'Info Label')
        ->first();

    expect($panel)
        ->not->toBeNull()
        ->dashboard_id->toBe($this->dashboard->id);
});

it('can delete a panel via the relation manager', function () {
    $panel = StudioPanel::factory()->forDashboard($this->dashboard)->create();

    Livewire::test(PanelsRelationManager::class, [
        'ownerRecord' => $this->dashboard,
        'pageClass' => EditDashboard::class,
    ])
        ->callTableAction('delete', $panel);

    expect(StudioPanel::find($panel->id))->toBeNull();
});

it('orders panels by grid_order', function () {
    $panelB = StudioPanel::factory()->forDashboard($this->dashboard)->create([
        'header_label' => 'B',
        'grid_order' => 2,
    ]);
    $panelA = StudioPanel::factory()->forDashboard($this->dashboard)->create([
        'header_label' => 'A',
        'grid_order' => 1,
    ]);

    Livewire::test(PanelsRelationManager::class, [
        'ownerRecord' => $this->dashboard,
        'pageClass' => EditDashboard::class,
    ])
        ->assertCanSeeTableRecords([$panelA, $panelB], inOrder: true);
});
