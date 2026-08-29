<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Contracts\Flows;

final readonly class DataChain
{
    /**
     * @param  array<string, mixed>  $trigger
     * @param  array<string, mixed>  $outputs
     */
    public function __construct(
        private array $trigger,
        private array $outputs,
        private mixed $last = null,
    ) {}

    /** @return array<string, mixed> */
    public function trigger(): array
    {
        return $this->trigger;
    }

    public function last(): mixed
    {
        return $this->last;
    }

    public function get(string $opKey): mixed
    {
        return $this->outputs[$opKey] ?? null;
    }

    /**
     * Returns a flat bag of all chain data keyed by reference name.
     *
     * Operation outputs are exposed both with and without a leading `$`
     * so templates may reference them as either `{{ op_key.field }}` or
     * `{{ $op_key.field }}`.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $prefixed = [];
        foreach ($this->outputs as $key => $value) {
            $prefixed['$'.$key] = $value;
        }

        return array_merge(
            ['$trigger' => $this->trigger, '$last' => $this->last],
            $this->outputs,
            $prefixed,
        );
    }
}
