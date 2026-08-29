<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Tests\Support\Flows;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;
use RuntimeException;

class BoomActivity implements FlowOperation
{
    public function execute(OperationContext $context): OperationResult
    {
        throw new RuntimeException('boom');
    }
}
