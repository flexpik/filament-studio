<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Database\Factories\Flows;

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowAuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudioFlowAuditLog>
 */
class StudioFlowAuditLogFactory extends Factory
{
    protected $model = StudioFlowAuditLog::class;

    public function definition(): array
    {
        return [
            'flow_id' => StudioFlow::factory(),
            'actor_id' => null,
            'actor_type' => 'system',
            'event' => 'created',
            'metadata' => [],
            'ip_address' => null,
        ];
    }
}
