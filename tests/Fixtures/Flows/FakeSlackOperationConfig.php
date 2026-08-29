<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Tests\Fixtures\Flows;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperationConfig;

class FakeSlackOperationConfig implements FlowOperationConfig
{
    public function schema(): array
    {
        return ['fields' => [['name' => 'webhook_url', 'type' => 'text']]];
    }

    public function defaults(): array
    {
        return [];
    }

    public function validate(array $config): void {}
}
