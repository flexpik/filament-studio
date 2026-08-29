<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Services;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowAuditLog;
use Illuminate\Contracts\Auth\Authenticatable;

class WriteFlowAuditLog
{
    public function write(
        StudioFlow $flow,
        string $event,
        array $metadata = [],
        ?Authenticatable $actor = null,
    ): StudioFlowAuditLog {
        return StudioFlowAuditLog::create([
            'flow_id' => $flow->id,
            'actor_id' => $actor !== null ? (string) $actor->getAuthIdentifier() : null,
            'actor_type' => $actor !== null ? 'user' : 'system',
            'event' => $event,
            'metadata' => $metadata,
            'ip_address' => request()->ip() ?? null,
        ]);
    }
}
