<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Tests\Fixtures\Flows;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;

class FakeSlackOperation implements FlowOperation
{
    public function execute(OperationContext $context): OperationResult
    {
        return OperationResult::success(['sent' => true]);
    }
}
