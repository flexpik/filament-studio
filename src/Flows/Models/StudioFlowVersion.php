<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Models;

use Flexpik\FilamentStudio\Database\Factories\Flows\StudioFlowVersionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudioFlowVersion extends Model
{
    /** @use HasFactory<StudioFlowVersionFactory> */
    use HasFactory;

    use HasUuids;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('filament-studio.table_prefix', 'studio_').'flow_versions';
    }

    protected function casts(): array
    {
        return [
            'graph' => 'array',
            'published_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(StudioFlow::class, 'flow_id');
    }

    public function isDraft(): bool
    {
        return $this->published_at === null;
    }

    public function isPublished(): bool
    {
        return ! $this->isDraft();
    }

    public function publish(?string $changeSummary = null): void
    {
        $this->forceFill([
            'published_at' => now(),
            'change_summary' => $changeSummary,
        ])->save();
    }

    protected static function newFactory(): StudioFlowVersionFactory
    {
        return StudioFlowVersionFactory::new();
    }
}
