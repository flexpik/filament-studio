<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Models;

use Flexpik\FilamentStudio\Database\Factories\Flows\StudioFlowSecretFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudioFlowSecret extends Model
{
    /** @use HasFactory<StudioFlowSecretFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('filament-studio.table_prefix', 'studio_').'flow_secrets';
    }

    protected function casts(): array
    {
        return ['value' => 'encrypted'];
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(StudioFlow::class, 'flow_id');
    }

    protected static function newFactory(): StudioFlowSecretFactory
    {
        return StudioFlowSecretFactory::new();
    }
}
