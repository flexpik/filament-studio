<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Api\Flows\Resources;

use Flexpik\FilamentStudio\Flows\Security\MasksSensitiveValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RunExportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $masker = app(MasksSensitiveValues::class);

        return [
            'id' => $this->id,
            'flow_id' => $this->flow_id,
            'flow_version_id' => $this->flow_version_id,
            'status' => $this->status?->value,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'duration_ms' => $this->duration_ms,
            'trigger_type' => $this->trigger_type,
            'trigger_payload' => $masker->mask((array) ($this->trigger_payload ?? [])),
            'steps' => $this->steps->map(fn ($step) => [
                'id' => $step->id,
                'operation_key' => $step->operation_key,
                'operation_type' => $step->operation_type,
                'attempt_number' => $step->attempt_number,
                'status' => $step->status?->value,
                'input' => $masker->mask((array) ($step->input ?? [])),
                'output' => $masker->mask((array) ($step->output ?? [])),
                'error_class' => $step->error_class,
                'error_message' => $step->error_message,
                'started_at' => $step->started_at,
                'finished_at' => $step->finished_at,
            ])->values()->toArray(),
        ];
    }
}
