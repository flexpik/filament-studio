<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Models;

use Flexpik\FilamentStudio\Database\Factories\Flows\StudioFlowAuditLogFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudioFlowAuditLog extends Model
{
    /** @use HasFactory<StudioFlowAuditLogFactory> */
    use HasFactory;

    use HasUuids;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function getTable(): string
    {
        return config('filament-studio.table_prefix', 'studio_').'flow_audit_log';
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(StudioFlow::class, 'flow_id');
    }

    protected static function newFactory(): StudioFlowAuditLogFactory
    {
        return StudioFlowAuditLogFactory::new();
    }
}
