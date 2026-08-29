<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Communication;

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperationConfig;
use Flexpik\FilamentStudio\Flows\Exceptions\InvalidOperationConfigException;

class SendEmailConfig implements FlowOperationConfig
{
    /** @return array<string, mixed> */
    public function schema(): array
    {
        return [
            'fields' => [
                ['name' => 'to', 'type' => 'text', 'label' => 'To'],
                ['name' => 'subject', 'type' => 'text', 'label' => 'Subject'],
                ['name' => 'body', 'type' => 'textarea', 'label' => 'Body'],
                ['name' => 'headers', 'type' => 'keyvalue', 'label' => 'Headers'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function defaults(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function validate(array $config): void
    {
        $to = $config['to'] ?? '';
        if (empty($to)) {
            throw new InvalidOperationConfigException(
                'to is required',
                ['to' => 'required'],
            );
        }

        // Skip strict email validation if template expression present
        if (! str_contains($to, '{{') && ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidOperationConfigException(
                "'{$to}' is not a valid email",
                ['to' => 'invalid email'],
            );
        }
    }
}
