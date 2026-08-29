<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Exceptions;

class InvalidOperationConfigException extends \RuntimeException
{
    /**
     * @param  array<string, mixed>  $errors
     */
    public function __construct(
        string $message = '',
        private readonly array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /** @return array<string, mixed> */
    public function errors(): array
    {
        return $this->errors;
    }
}
