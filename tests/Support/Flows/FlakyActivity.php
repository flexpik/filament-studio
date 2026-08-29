<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Tests\Support\Flows;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;
use RuntimeException;

/**
 * Test fixture: fails a configurable number of times then succeeds.
 * Uses a static counter so retries within the same request see accumulating state.
 */
class FlakyActivity implements FlowOperation
{
    private static int $callCount = 0;

    private static int $failTimes = 0;

    public static function reset(int $failTimes = 1): void
    {
        self::$callCount = 0;
        self::$failTimes = $failTimes;
    }

    public function execute(OperationContext $context): OperationResult
    {
        self::$callCount++;

        if (self::$callCount <= self::$failTimes) {
            throw new RuntimeException('flaky failure #'.self::$callCount);
        }

        return OperationResult::success(['attempt' => self::$callCount]);
    }
}
