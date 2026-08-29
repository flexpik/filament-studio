<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Triggers;

use Flexpik\FilamentStudio\Flows\Exceptions\DuplicateFlowTriggerException;
use Flexpik\FilamentStudio\Flows\Operations\Validation\ConfigSchemaValidator;
use InvalidArgumentException;

class TriggerRegistry
{
    /** @var array<string, array{label: string, trigger: class-string, configSchema: ?string, icon: ?string}> */
    private array $entries = [];

    public function register(
        string $key,
        string $label,
        string $handler,
        ?string $configSchema = null,
        ?string $icon = null,
    ): void {
        if ($this->has($key)) {
            throw DuplicateFlowTriggerException::forKey($key);
        }

        if ($configSchema !== null) {
            ConfigSchemaValidator::validate(app($configSchema));
        }

        $this->entries[$key] = ['label' => $label, 'trigger' => $handler, 'configSchema' => $configSchema, 'icon' => $icon];
    }

    public function has(string $key): bool
    {
        return isset($this->entries[$key]);
    }

    public function resolve(string $key): object
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Unknown flow trigger: {$key}");
        }

        return app($this->entries[$key]['trigger']);
    }

    /** @return array<string, array{label: string, trigger: string, configSchema: ?string, icon: ?string}> */
    public function all(): array
    {
        return $this->entries;
    }
}
