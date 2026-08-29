<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Api\Flows\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlowRunStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'operation_key' => $this->operation_key,
            'operation_type' => $this->operation_type,
            'attempt_number' => $this->attempt_number,
            'status' => $this->status?->value,
            'input' => $this->input,
            'output' => $this->output,
            'error_message' => $this->error_message,
            'error_class' => $this->error_class,
            'error_trace' => $this->error_trace,
            'branch_taken' => $this->branch_taken,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
        ];
    }
}
