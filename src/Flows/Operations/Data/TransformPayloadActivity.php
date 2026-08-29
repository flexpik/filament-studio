<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Data;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;

class TransformPayloadActivity implements FlowOperation
{
    public function execute(OperationContext $context): OperationResult
    {
        $payload = $context->config()['payload'] ?? [];

        return OperationResult::success(is_array($payload) ? $payload : ['value' => $payload]);
    }
}
