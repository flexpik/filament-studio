<?php

namespace Flexpik\FilamentStudio\Models;

use Flexpik\FilamentStudio\Database\Factories\StudioCollectionFactory;
use Flexpik\FilamentStudio\Enums\SortDirection;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property string $name
 * @property string $label
 * @property string $label_plural
 * @property string $slug
 * @property string|null $icon
 * @property string|null $description
 * @property bool $is_singleton
 * @property bool $is_hidden
 * @property bool $api_enabled
 * @property string|null $sort_field
 * @property SortDirection $sort_direction
 * @property bool $enable_versioning
 * @property bool $enable_soft_deletes
 * @property string|null $archive_field
 * @property string|null $archive_value
 * @property string|null $display_template
 * @property array|null $translations
 * @property array|null $supported_locales
 * @property string|null $default_locale
 * @property array|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, StudioField> $fields
 * @property-read Collection<int, StudioRecord> $records
 * @property-read Collection<int, StudioMigrationLog> $migrationLogs
 */
class StudioCollection extends Model
{
    /** @use HasFactory<StudioCollectionFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('filament-studio.table_prefix', 'studio_').'collections';
    }

    protected function casts(): array
    {
        return [
            'is_singleton' => 'boolean',
            'is_hidden' => 'boolean',
            'api_enabled' => 'boolean',
            'sort_direction' => SortDirection::class,
            'enable_versioning' => 'boolean',
            'enable_soft_deletes' => 'boolean',
            'translations' => 'array',
            'settings' => 'array',
            'supported_locales' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (StudioCollection $collection) {
            EavQueryBuilder::invalidateFieldCache($collection->id);
        });

        static::deleted(function (StudioCollection $collection) {
            EavQueryBuilder::invalidateFieldCache($collection->id);
        });
    }

    public function fields(): HasMany
    {
        return $this->hasMany(StudioField::class, 'collection_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(StudioRecord::class, 'collection_id');
    }

    public function migrationLogs(): HasMany
    {
        return $this->hasMany(StudioMigrationLog::class, 'collection_id');
    }

    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    public function scopeForTenant($query, ?int $tenantId)
    {
        if ($tenantId === null) {
            return $query;
        }

        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('label');
    }

    public function scopeApiEnabled($query)
    {
        return $query->where('api_enabled', true);
    }

    protected static function newFactory(): StudioCollectionFactory
    {
        return StudioCollectionFactory::new();
    }
}
