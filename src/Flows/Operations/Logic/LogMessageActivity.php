<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Logic;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;
use Illuminate\Support\Facades\Log;

class LogMessageActivity implements FlowOperation
{
    public function execute(OperationContext $context): OperationResult
    {
        $config = $context->config();
        $level = strtolower((string) ($config['level'] ?? 'info'));
        $message = (string) ($config['message'] ?? '');
        $channel = (string) config('filament-studio.flows.log_channel', 'daily');

        $allowed = ['debug', 'info', 'notice', 'warning', 'error'];
        if (! in_array($level, $allowed, true)) {
            $level = 'info';
        }

        Log::channel($channel)->{$level}($message);

        return OperationResult::success(['logged' => true, 'level' => $level, 'message' => $message]);
    }
}
