<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Tests\Fixtures\Flows;

use Flexpik\FilamentStudio\Contracts\Flows\FlowTriggerConfig;

class FakeStripeTriggerConfig implements FlowTriggerConfig
{
    public function schema(): array
    {
        return [];
    }

    public function defaults(): array
    {
        return [];
    }

    public function validate(array $config): void {}
}
