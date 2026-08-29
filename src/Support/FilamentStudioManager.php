<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Support;

use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;
use Flexpik\FilamentStudio\Flows\Triggers\TriggerRegistry;

class FilamentStudioManager
{
    public function __construct(
        private readonly OperationRegistry $operations,
        private readonly TriggerRegistry $triggers,
    ) {}

    public function registerFlowOperation(
        string $key,
        string $label,
        string $activity,
        ?string $configSchema = null,
        ?string $icon = null,
        string $group = 'general',
        bool $dangerous = false,
    ): void {
        $this->operations->register($key, $label, $activity, $configSchema, $icon, $group, $dangerous);
    }

    public function registerFlowTrigger(
        string $key,
        string $label,
        string $handler,
        ?string $configSchema = null,
        ?string $icon = null,
    ): void {
        $this->triggers->register($key, $label, $handler, $configSchema, $icon);
    }
}
