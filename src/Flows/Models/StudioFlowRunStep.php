<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Models;

use Flexpik\FilamentStudio\Database\Factories\Flows\StudioFlowRunStepFactory;
use Flexpik\FilamentStudio\Flows\Enums\FlowRunStepStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudioFlowRunStep extends Model
{
    /** @use HasFactory<StudioFlowRunStepFactory> */
    use HasFactory;

    use HasUuids;

    public $timestamps = false;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('filament-studio.table_prefix', 'studio_').'flow_run_steps';
    }

    protected function casts(): array
    {
        return [
            'status' => FlowRunStepStatus::class,
            'input' => 'array',
            'output' => 'array',
            'attempt_number' => 'integer',
            'error_class' => 'string',
            'branch_taken' => 'string',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(StudioFlowRun::class, 'flow_run_id');
    }

    protected static function newFactory(): StudioFlowRunStepFactory
    {
        return StudioFlowRunStepFactory::new();
    }
}
