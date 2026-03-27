<?php

namespace Flexpik\FilamentStudio\Database\Factories;

use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudioPanel>
 */
class StudioPanelFactory extends Factory
{
    protected $model = StudioPanel::class;

    public function definition(): array
    {
        return [
            'tenant_id' => $this->faker->numberBetween(1, 100),
            'dashboard_id' => StudioDashboard::factory(),
            'placement' => PanelPlacement::Dashboard,
            'context_collection_id' => null,
            'panel_type' => 'metric',
            'header_visible' => true,
            'header_label' => $this->faker->words(2, true),
            'header_icon' => null,
            'header_color' => null,
            'header_note' => null,
            'grid_col_span' => 6,
            'grid_row_span' => 4,
            'grid_order' => 0,
            'sort_order' => 0,
            'config' => [],
        ];
    }

    public function forDashboard(StudioDashboard $dashboard): static
    {
        return $this->state([
            'dashboard_id' => $dashboard->id,
            'tenant_id' => $dashboard->tenant_id,
            'placement' => PanelPlacement::Dashboard,
        ]);
    }

    public function forCollectionHeader(int $collectionId): static
    {
        return $this->state([
            'dashboard_id' => null,
            'placement' => PanelPlacement::CollectionHeader,
            'context_collection_id' => $collectionId,
        ]);
    }

    public function forCollectionFooter(int $collectionId): static
    {
        return $this->state([
            'dashboard_id' => null,
            'placement' => PanelPlacement::CollectionFooter,
            'context_collection_id' => $collectionId,
        ]);
    }

    public function forRecordHeader(int $collectionId): static
    {
        return $this->state([
            'dashboard_id' => null,
            'placement' => PanelPlacement::RecordHeader,
            'context_collection_id' => $collectionId,
        ]);
    }
}
