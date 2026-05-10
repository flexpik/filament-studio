<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Tests\Support\Flows;

use Flexpik\FilamentStudio\Flows\Engine\FlowContext;
use Flexpik\FilamentStudio\Flows\Operations\FlowOperationActivity;
use RuntimeException;

class BoomActivity implements FlowOperationActivity
{
    public function execute(array $config, FlowContext $context): mixed
    {
        throw new RuntimeException('boom');
    }
}
