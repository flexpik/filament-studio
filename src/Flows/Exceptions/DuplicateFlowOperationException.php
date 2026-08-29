<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Exceptions;

class DuplicateFlowOperationException extends \RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self("Flow operation '{$key}' is already registered");
    }
}
