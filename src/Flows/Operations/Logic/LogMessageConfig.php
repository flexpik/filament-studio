<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Logic;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperationConfig;
use Flexpik\FilamentStudio\Flows\Exceptions\InvalidOperationConfigException;

class LogMessageConfig implements FlowOperationConfig
{
    /** @return array<string, mixed> */
    public function schema(): array
    {
        return [
            'fields' => [
                ['name' => 'level', 'type' => 'select', 'label' => 'Level', 'options' => ['debug', 'info', 'warning', 'error']],
                ['name' => 'message', 'type' => 'textarea', 'label' => 'Message'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return ['level' => 'info', 'message' => ''];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function validate(array $config): void
    {
        $valid = ['debug', 'info', 'notice', 'warning', 'error'];
        $level = $config['level'] ?? 'info';
        if (! in_array($level, $valid, true)) {
            throw new InvalidOperationConfigException(
                "Invalid log level: {$level}",
                ['level' => 'must be one of: '.implode(', ', $valid)],
            );
        }
    }
}
