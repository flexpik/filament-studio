<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Models;

use Flexpik\FilamentStudio\Database\Factories\Flows\StudioFlowRunFactory;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudioFlowRun extends Model
{
    /** @use HasFactory<StudioFlowRunFactory> */
    use HasFactory;

    use HasUuids;
    use MassPrunable;

    public $timestamps = false;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('filament-studio.table_prefix', 'studio_').'flow_runs';
    }

    protected function casts(): array
    {
        return [
            'status' => FlowRunStatus::class,
            'trigger_payload' => 'array',
            'accountability' => 'array',
            'inline_graph' => 'array',
            'dry_run' => 'boolean',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_ms' => 'integer',
        ];
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(StudioFlow::class, 'flow_id');
    }

    public function flowVersion(): BelongsTo
    {
        return $this->belongsTo(StudioFlowVersion::class, 'flow_version_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(StudioFlowRunStep::class, 'flow_run_id');
    }

    public function prunable(): Builder
    {
        $days = (int) config('filament-studio.flows.log_retention_days', 30);

        return static::query()->whereNotNull('finished_at')->where('finished_at', '<=', now()->subDays($days));
    }

    protected static function newFactory(): StudioFlowRunFactory
    {
        return StudioFlowRunFactory::new();
    }
}
