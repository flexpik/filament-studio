<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Triggers\Schedule;

use Carbon\CarbonImmutable;
use Cron\CronExpression;

class CronEvaluator
{
    public function isDue(string $expression, string $timezone, CarbonImmutable $now): bool
    {
        $cron = new CronExpression($expression);
        $local = $now->setTimezone($timezone);

        return $cron->isDue($local->format('Y-m-d H:i'));
    }
}
