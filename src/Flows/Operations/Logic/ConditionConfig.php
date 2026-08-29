<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Logic;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperationConfig;
use Flexpik\FilamentStudio\Flows\Exceptions\InvalidOperationConfigException;

class ConditionConfig implements FlowOperationConfig
{
    /** @return array<string, mixed> */
    public function schema(): array
    {
        return [
            'fields' => [
                ['name' => 'filter', 'type' => 'code', 'label' => 'Filter'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [
            'filter' => ['logic' => 'and', 'rules' => []],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function validate(array $config): void
    {
        $logic = $config['filter']['logic'] ?? 'and';
        if (! in_array($logic, ['and', 'or'], true)) {
            throw new InvalidOperationConfigException(
                "filter.logic must be 'and' or 'or', got '{$logic}'",
                ['filter.logic' => "must be 'and' or 'or'"],
            );
        }
    }
}
