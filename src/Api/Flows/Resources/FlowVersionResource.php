<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Api\Flows\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlowVersionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'flow_id' => $this->flow_id,
            'version' => $this->version,
            'graph' => $this->graph,
            'published_at' => $this->published_at,
            'published_by' => $this->published_by,
            'change_summary' => $this->change_summary,
            'created_at' => $this->created_at,
        ];
    }
}
