<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Contracts\Flows;

use Flexpik\FilamentStudio\Flows\Exceptions\InvalidOperationConfigException;

interface FlowOperationConfig
{
    /** @return array<string, mixed> */
    public function schema(): array;

    /** @return array<string, mixed> */
    public function defaults(): array;

    /**
     * @param  array<string, mixed>  $config
     *
     * @throws InvalidOperationConfigException
     */
    public function validate(array $config): void;
}
