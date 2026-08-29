<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Communication;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperationConfig;
use Flexpik\FilamentStudio\Flows\Exceptions\InvalidOperationConfigException;

class HttpRequestConfig implements FlowOperationConfig
{
    /** @return array<string, mixed> */
    public function schema(): array
    {
        return [
            'fields' => [
                ['name' => 'method', 'type' => 'select', 'label' => 'Method'],
                ['name' => 'url', 'type' => 'text', 'label' => 'URL'],
                ['name' => 'headers', 'type' => 'keyvalue', 'label' => 'Headers'],
                ['name' => 'body', 'type' => 'code', 'label' => 'Body'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return ['method' => 'GET', 'timeout_seconds' => 30, 'fail_on_error' => true];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function validate(array $config): void
    {
        $valid = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        if (! in_array(strtoupper($config['method'] ?? ''), $valid, true)) {
            throw new InvalidOperationConfigException(
                'invalid method',
                ['method' => 'invalid'],
            );
        }
    }
}
