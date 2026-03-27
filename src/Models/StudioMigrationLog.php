<?php

namespace Flexpik\FilamentStudio\Models;

use Flexpik\FilamentStudio\Database\Factories\StudioMigrationLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $collection_id
 * @property int|null $field_id
 * @property string $operation
 * @property array|null $before_state
 * @property array|null $after_state
 * @property int|null $performed_by
 * @property Carbon|null $created_at
 * @property-read StudioCollection|null $collection
 * @property-read StudioField|null $field
 * @property-read Model|null $performer
 */
class StudioMigrationLog extends Model
{
    /** @use HasFactory<StudioMigrationLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('filament-studio.table_prefix', 'studio_').'migration_logs';
    }

    protected function casts(): array
    {
        return [
            'before_state' => 'array',
            'after_state' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(StudioCollection::class, 'collection_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(StudioField::class, 'field_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'performed_by');
    }

    public function scopeForTenant($query, ?int $tenantId)
    {
        if ($tenantId === null) {
            return $query;
        }

        return $query->where('tenant_id', $tenantId);
    }

    protected static function newFactory(): StudioMigrationLogFactory
    {
        return StudioMigrationLogFactory::new();
    }
}
