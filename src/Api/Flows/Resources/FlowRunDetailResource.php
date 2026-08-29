<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Api\Flows\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlowRunDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'flow_id' => $this->flow_id,
            'flow_version_id' => $this->flow_version_id,
            'status' => $this->status?->value,
            'trigger_type' => $this->trigger_type,
            'trigger_payload' => $this->trigger_payload,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'duration_ms' => $this->duration_ms,
            'accountability' => $this->accountability,
            'steps' => FlowRunStepResource::collection($this->steps()->orderBy('started_at')->get()),
        ];
    }
}
