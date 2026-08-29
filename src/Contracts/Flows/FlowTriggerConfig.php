<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Contracts\Flows;

interface FlowTriggerConfig
{
    /** @return array<string, mixed> */
    public function schema(): array;

    /** @return array<string, mixed> */
    public function defaults(): array;

    /**
     * @param  array<string, mixed>  $config
     */
    public function validate(array $config): void;
}
