<?php

namespace Flexpik\FilamentStudio\Models;

use Flexpik\FilamentStudio\Database\Factories\StudioRecordVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $record_id
 * @property int $collection_id
 * @property int|null $tenant_id
 * @property array $snapshot
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property-read StudioRecord $record
 * @property-read StudioCollection $collection
 * @property-read \Illuminate\Database\Eloquent\Model|null $creator
 */
class StudioRecordVersion extends Model
{
    /** @use HasFactory<StudioRecordVersionFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('filament-studio.table_prefix', 'studio_').'record_versions';
    }

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(StudioRecord::class, 'record_id');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(StudioCollection::class, 'collection_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model', \Illuminate\Foundation\Auth\User::class),
            'created_by'
        );
    }

    public function scopeForTenant($query, ?int $tenantId)
    {
        if ($tenantId === null) {
            return $query;
        }

        return $query->where('tenant_id', $tenantId);
    }

    protected static function newFactory(): StudioRecordVersionFactory
    {
        return StudioRecordVersionFactory::new();
    }
}
