<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;

class NoOpActivity implements FlowOperation
{
    public function execute(OperationContext $context): OperationResult
    {
        return OperationResult::success($context->config());
    }
}
