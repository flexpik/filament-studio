<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Models;

use Flexpik\FilamentStudio\Database\Factories\Flows\StudioFlowFactory;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Enums\LoggingMode;
use Flexpik\FilamentStudio\Flows\Enums\WebhookAuthMode;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudioFlow extends Model
{
    /** @use HasFactory<StudioFlowFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = ['id'];

    protected $attributes = [
        'status' => FlowStatus::Inactive->value,
    ];

    public function getTable(): string
    {
        return config('filament-studio.table_prefix', 'studio_').'flows';
    }

    protected function casts(): array
    {
        return [
            'status' => FlowStatus::class,
            'logging_mode' => LoggingMode::class,
            'webhook_secret' => 'encrypted',
            'webhook_auth_mode' => WebhookAuthMode::class,
            'webhook_allowed_studio_api_key_ids' => 'array',
            'webhook_redact_paths' => 'array',
            'draft_graph' => 'array',
            'draft_updated_at' => 'datetime',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(StudioFlowVersion::class, 'flow_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(StudioFlowRun::class, 'flow_id');
    }

    public function secrets(): HasMany
    {
        return $this->hasMany(StudioFlowSecret::class, 'flow_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(StudioFlowAuditLog::class, 'flow_id');
    }

    public function publishedVersion(): BelongsTo
    {
        return $this->belongsTo(StudioFlowVersion::class, 'published_version_id');
    }

    protected static function newFactory(): StudioFlowFactory
    {
        return StudioFlowFactory::new();
    }
}
