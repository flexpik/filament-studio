<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Exceptions;

use RuntimeException;

class StudioMcpAuthorizationException extends RuntimeException
{
    /** @param array<string, mixed> $data */
    public function __construct(string $message, protected array $data = [])
    {
        parent::__construct($message);
    }

    /** @return array<string, mixed> */
    public function data(): array
    {
        return $this->data;
    }

    public function code(): string
    {
        return 'STUDIO_UNAUTHORIZED';
    }
}
