<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Enums;

enum LoggingMode: string
{
    case Full = 'full';
    case ErrorsOnly = 'errors_only';
    case Disabled = 'disabled';
}
