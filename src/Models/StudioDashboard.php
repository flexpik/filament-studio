<?php

namespace Flexpik\FilamentStudio\Models;

use Flexpik\FilamentStudio\Database\Factories\StudioDashboardFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property string|null $color
 * @property int|null $auto_refresh_interval
 * @property int $sort_order
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, StudioPanel> $panels
 */
class StudioDashboard extends Model
{
    /** @use HasFactory<StudioDashboardFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('filament-studio.table_prefix', 'studio_').'dashboards';
    }

    protected function casts(): array
    {
        return [
            'auto_refresh_interval' => 'integer',
            'sort_order' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function panels(): HasMany
    {
        return $this->hasMany(StudioPanel::class, 'dashboard_id');
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
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    protected static function newFactory(): StudioDashboardFactory
    {
        return StudioDashboardFactory::new();
    }
}
