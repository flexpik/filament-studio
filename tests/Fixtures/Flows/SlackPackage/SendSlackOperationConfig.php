<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Tests\Fixtures\Flows\SlackPackage;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperationConfig;
use Flexpik\FilamentStudio\Flows\Exceptions\InvalidOperationConfigException;

class SendSlackOperationConfig implements FlowOperationConfig
{
    public function schema(): array
    {
        return ['fields' => [
            ['name' => 'webhook_url', 'type' => 'text', 'label' => 'Webhook URL'],
            ['name' => 'message', 'type' => 'textarea', 'label' => 'Message'],
        ]];
    }

    public function defaults(): array
    {
        return [];
    }

    public function validate(array $config): void
    {
        if (empty($config['webhook_url'] ?? '')) {
            throw new InvalidOperationConfigException(
                'webhook_url is required',
                ['webhook_url' => 'required'],
            );
        }
    }
}
