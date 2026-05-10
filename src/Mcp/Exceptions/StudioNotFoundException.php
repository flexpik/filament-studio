<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Exceptions;

use RuntimeException;

class StudioNotFoundException extends RuntimeException
{
    public function __construct(public readonly string $resource, public readonly string $identifier)
    {
        parent::__construct(sprintf("%s '%s' was not found.", ucfirst($resource), $identifier));
    }

    public function mcpCode(): string
    {
        return 'STUDIO_NOT_FOUND';
    }

    /** @return array<string, mixed> */
    public function mcpData(): array
    {
        return [
            'resource' => $this->resource,
            'identifier' => $this->identifier,
        ];
    }
}
