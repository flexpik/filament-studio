<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Exceptions;

class InvalidFlowGraphException extends \RuntimeException
{
    /** @param array<string, string> $errors */
    public function __construct(
        private readonly array $errors,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message ?: $this->buildMessage(), $code, $previous);
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    private function buildMessage(): string
    {
        return implode("\n", array_map(
            fn ($nodeId, $reason) => "• {$nodeId}: {$reason}",
            array_keys($this->errors),
            $this->errors,
        ));
    }
}
