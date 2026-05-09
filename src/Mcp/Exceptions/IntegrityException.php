<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Exceptions;

use RuntimeException;

class IntegrityException extends RuntimeException
{
    private function __construct(public readonly string $field, public readonly mixed $value, string $message)
    {
        parent::__construct($message);
    }

    public static function duplicate(string $field, mixed $value): self
    {
        return new self($field, $value, sprintf("Duplicate value for '%s': %s", $field, (string) $value));
    }

    public function mcpCode(): string
    {
        return 'STUDIO_INTEGRITY_VIOLATION';
    }

    /** @return array<string, mixed> */
    public function mcpData(): array
    {
        return [
            'field' => $this->field,
            'conflicting_value' => $this->value,
        ];
    }
}
