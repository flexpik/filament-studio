<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Contracts\Flows;

interface FlowOperation
{
    public function execute(OperationContext $context): OperationResult;
}
