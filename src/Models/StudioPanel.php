<?php

namespace Flexpik\FilamentStudio\Models;

use Flexpik\FilamentStudio\Database\Factories\StudioPanelFactory;
use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Panels\PanelTypeRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $dashboard_id
 * @property PanelPlacement $placement
 * @property int|null $context_collection_id
 * @property string $panel_type
 * @property bool $header_visible
 * @property string|null $header_label
 * @property string|null $header_icon
 * @property string|null $header_color
 * @property string|null $header_note
 * @property int $grid_col_span
 * @property int $grid_row_span
 * @property int $grid_order
 * @property int $sort_order
 * @property array|null $config
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read StudioDashboard|null $dashboard
 * @property-read StudioCollection|null $contextCollection
 */
class StudioPanel extends Model
{
    /** @use HasFactory<StudioPanelFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('filament-studio.table_prefix', 'studio_').'panels';
    }

    protected function casts(): array
    {
        return [
            'placement' => PanelPlacement::class,
            'header_visible' => 'boolean',
            'grid_col_span' => 'integer',
            'grid_row_span' => 'integer',
            'grid_order' => 'integer',
            'sort_order' => 'integer',
            'config' => 'array',
        ];
    }

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(StudioDashboard::class, 'dashboard_id');
    }

    public function contextCollection(): BelongsTo
    {
        return $this->belongsTo(StudioCollection::class, 'context_collection_id');
    }

    /**
     * Merge panel-type defaults into the stored config so Livewire
     * can always find every property the config schema references.
     *
     * @return array<string, mixed>
     */
    public function getMergedConfigAttribute(): array
    {
        $stored = $this->config ?? [];

        try {
            $panelClass = app(PanelTypeRegistry::class)->get($this->panel_type);

            return array_merge($panelClass::defaultConfig(), $stored);
        } catch (\InvalidArgumentException) {
            return $stored;
        }
    }

    /**
     * Get a value from the config JSON.
     */
    public function configValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForTenant(Builder $query, ?int $tenantId = null): Builder
    {
        if ($tenantId === null) {
            return $query;
        }

        return $query->where('tenant_id', $tenantId);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForDashboard(Builder $query, int $dashboardId): Builder
    {
        return $query->where('dashboard_id', $dashboardId)
            ->where('placement', PanelPlacement::Dashboard)
            ->orderBy('grid_order');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForPlacement(Builder $query, PanelPlacement $placement, ?int $collectionId = null): Builder
    {
        $query->where('placement', $placement);

        if ($collectionId !== null) {
            $query->where('context_collection_id', $collectionId);
        }

        return $query->orderBy('sort_order');
    }

    protected static function newFactory(): StudioPanelFactory
    {
        return StudioPanelFactory::new();
    }
}
