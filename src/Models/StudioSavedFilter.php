<?php

namespace Flexpik\FilamentStudio\Models;

use App\Models\User;
use Flexpik\FilamentStudio\Filtering\FilterGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $collection_id
 * @property int|null $tenant_id
 * @property int|null $created_by
 * @property string $name
 * @property bool $is_shared
 * @property array $filter_tree
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read StudioCollection $collection
 * @property-read Model|null $creator
 */
class StudioSavedFilter extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_shared' => 'boolean',
            'filter_tree' => 'array',
        ];
    }

    public function getTable(): string
    {
        $prefix = config('filament-studio.table_prefix', 'studio_');

        return "{$prefix}saved_filters";
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(StudioCollection::class, 'collection_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', User::class), 'created_by');
    }

    public function toFilterGroup(): FilterGroup
    {
        return FilterGroup::fromArray($this->filter_tree ?? ['logic' => 'and', 'rules' => []]);
    }

    /**
     * Scope to filters visible to a specific user: their own + shared.
     */
    public function scopeVisibleTo(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->where('created_by', $userId)
                ->orWhere('is_shared', true);
        });
    }

    public function scopeForCollection(Builder $query, int $collectionId): Builder
    {
        return $query->where('collection_id', $collectionId);
    }

    public function scopeForTenant(Builder $query, ?int $tenantId): Builder
    {
        if ($tenantId === null) {
            return $query;
        }

        return $query->where('tenant_id', $tenantId);
    }
}
