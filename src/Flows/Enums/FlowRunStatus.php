<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Enums;

enum FlowRunStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Paused = 'paused';
    case Aborted = 'aborted';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Cancelled, self::Aborted], true);
    }
}
