<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Enums;

enum FlowRunStepStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
