<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Engine;

class FlowContext
{
    /** @var array<string, mixed> */
    private array $outputs = [];

    private mixed $last = null;

    private function __construct(
        private array $trigger,
        private array $accountability,
        private array $secrets = [],
        private bool $dryRun = false,
    ) {}

    public static function make(array $trigger = [], array $accountability = [], array $secrets = [], bool $dryRun = false): self
    {
        return new self($trigger, $accountability, $secrets, $dryRun);
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    public function trigger(): array
    {
        return $this->trigger;
    }

    public function accountability(): array
    {
        return $this->accountability;
    }

    public function set(string $operationKey, mixed $output): void
    {
        $this->outputs[$operationKey] = $output;
        $this->last = $output;
    }

    public function get(string $operationKey): mixed
    {
        return $this->outputs[$operationKey] ?? null;
    }

    public function last(): mixed
    {
        return $this->last;
    }

    public function toArray(): array
    {
        return [
            '$trigger' => $this->trigger,
            '$last' => $this->last,
            '$accountability' => $this->accountability,
            '$secrets' => $this->secrets,
        ] + $this->outputs;
    }

    /** @return array<string, mixed> */
    public function toCache(): array
    {
        return [
            'trigger' => $this->trigger,
            'accountability' => $this->accountability,
            'secrets' => $this->secrets,
            'dry_run' => $this->dryRun,
            'outputs' => $this->outputs,
            'last' => $this->last,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromCache(array $data): self
    {
        $ctx = new self(
            $data['trigger'] ?? [],
            $data['accountability'] ?? [],
            $data['secrets'] ?? [],
            (bool) ($data['dry_run'] ?? false),
        );

        foreach ($data['outputs'] ?? [] as $key => $value) {
            $ctx->set($key, $value);
        }

        $ctx->restoreLast($data['last'] ?? null);

        return $ctx;
    }

    private function restoreLast(mixed $last): void
    {
        $this->last = $last;
    }
}
