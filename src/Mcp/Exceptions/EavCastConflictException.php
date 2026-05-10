<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Exceptions;

use RuntimeException;

class EavCastConflictException extends RuntimeException
{
    public function __construct(
        public readonly string $field,
        public readonly string $from,
        public readonly string $to,
        public readonly int $valueCount,
    ) {
        parent::__construct(sprintf(
            "Cannot change field '%s' from %s to %s — %d stored values would be lost.",
            $field, $from, $to, $valueCount,
        ));
    }

    public function mcpCode(): string
    {
        return 'STUDIO_EAV_CAST_CONFLICT';
    }

    /** @return array<string, mixed> */
    public function mcpData(): array
    {
        return [
            'field' => $this->field,
            'from_cast' => $this->from,
            'to_cast' => $this->to,
            'value_count' => $this->valueCount,
            'suggested_action' => 'Create a new field with the desired type and migrate values, then delete the old field.',
        ];
    }
}
