<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Exceptions;

class DuplicateFlowTriggerException extends \RuntimeException
{
    public static function forKey(string $key): self
    {
        return new self("Flow trigger '{$key}' is already registered");
    }
}
