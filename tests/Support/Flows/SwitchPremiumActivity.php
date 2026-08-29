<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Tests\Support\Flows;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;

/**
 * Test fixture: always routes to the 'premium' branch.
 */
class SwitchPremiumActivity implements FlowOperation
{
    public function execute(OperationContext $context): OperationResult
    {
        return OperationResult::withBranch('premium', ['plan' => 'premium']);
    }
}
