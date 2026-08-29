<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Observers;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowAuditLog;

class StudioFlowObserver
{
    /** @var list<string> */
    private const SENSITIVE_KEYS = ['webhook_secret'];

    public function created(StudioFlow $flow): void
    {
        $this->writeAuditLog($flow, 'created');
    }

    public function updated(StudioFlow $flow): void
    {
        $dirty = array_diff_key($flow->getDirty(), array_flip(self::SENSITIVE_KEYS));

        $this->writeAuditLog($flow, 'updated', $dirty);
    }

    public function deleted(StudioFlow $flow): void
    {
        $this->writeAuditLog($flow, 'deleted');
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function writeAuditLog(StudioFlow $flow, string $event, array $metadata = []): void
    {
        StudioFlowAuditLog::create([
            'flow_id' => $flow->id,
            'actor_id' => auth()->id() !== null ? (string) auth()->id() : null,
            'actor_type' => auth()->check() ? 'user' : 'system',
            'event' => $event,
            'metadata' => $metadata,
            'ip_address' => request()->ip() ?? null,
        ]);
    }
}
