<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Records;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperationConfig;
use Flexpik\FilamentStudio\Flows\Exceptions\InvalidOperationConfigException;

class CreateRecordConfig implements FlowOperationConfig
{
    /** @return array<string, mixed> */
    public function schema(): array
    {
        return [
            'fields' => [
                ['name' => 'collection', 'type' => 'text', 'label' => 'Collection Slug'],
                ['name' => 'data', 'type' => 'code', 'label' => 'Data'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return ['data' => []];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function validate(array $config): void
    {
        if (empty($config['collection'] ?? '')) {
            throw new InvalidOperationConfigException(
                'collection is required',
                ['collection' => 'required'],
            );
        }
    }
}
