<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Contracts\Flows;

final readonly class OperationResult
{
    private function __construct(
        private bool $success,
        private array $output,
        private ?string $branch,
        private ?string $message,
        private ?\Throwable $previous,
    ) {}

    public static function success(array $output = []): self
    {
        return new self(true, $output, null, null, null);
    }

    public static function fail(string $message, ?\Throwable $previous = null): self
    {
        return new self(false, [], null, $message, $previous);
    }

    public static function withBranch(string $branch, array $output = []): self
    {
        return new self(true, $output, $branch, null, null);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return ! $this->success;
    }

    public function output(): array
    {
        return $this->output;
    }

    public function branch(): ?string
    {
        return $this->branch;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    public function previous(): ?\Throwable
    {
        return $this->previous;
    }
}
