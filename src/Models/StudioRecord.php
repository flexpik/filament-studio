<?php

namespace Flexpik\FilamentStudio\Models;

use Flexpik\FilamentStudio\Database\Factories\StudioRecordFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property int $collection_id
 * @property int|null $tenant_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read StudioCollection $collection
 * @property-read Collection<int, StudioValue> $values
 * @property-read Collection<int, StudioRecordVersion> $versions
 */
class StudioRecord extends Model
{
    /** @use HasFactory<StudioRecordFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('filament-studio.table_prefix', 'studio_').'records';
    }

    protected static function booted(): void
    {
        static::creating(function (StudioRecord $record) {
            if (empty($record->uuid)) {
                $record->uuid = Str::uuid()->toString();
            }
        });
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(StudioCollection::class, 'collection_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(StudioValue::class, 'record_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(StudioRecordVersion::class, 'record_id');
    }

    public function scopeForTenant($query, ?int $tenantId)
    {
        if ($tenantId === null) {
            return $query;
        }

        return $query->where('tenant_id', $tenantId);
    }

    protected static function newFactory(): StudioRecordFactory
    {
        return StudioRecordFactory::new();
    }
}
