<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations;

use Flexpik\FilamentStudio\Flows\Exceptions\DuplicateFlowOperationException;
use Flexpik\FilamentStudio\Flows\Operations\Validation\ConfigSchemaValidator;
use InvalidArgumentException;

class OperationRegistry
{
    /** @var array<string, array{label: string, activity: class-string, configSchema: ?string, icon: ?string, group: string, dangerous: bool}> */
    private array $entries = [];

    public function register(
        string $key,
        string $label,
        string $activity,
        ?string $configSchema = null,
        ?string $icon = null,
        string $group = 'general',
        bool $dangerous = false,
    ): void {
        if ($this->has($key)) {
            throw DuplicateFlowOperationException::forKey($key);
        }

        if ($configSchema !== null) {
            ConfigSchemaValidator::validate(app($configSchema));
        }

        $this->entries[$key] = compact('label', 'activity', 'configSchema', 'icon', 'group', 'dangerous');
    }

    public function has(string $key): bool
    {
        return isset($this->entries[$key]);
    }

    public function resolve(string $key): object
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Unknown flow operation: {$key}");
        }

        return app($this->entries[$key]['activity']);
    }

    public function labelFor(string $key): string
    {
        return $this->entries[$key]['label'] ?? $key;
    }

    public function configFor(string $key): ?string
    {
        return $this->entries[$key]['configSchema'] ?? null;
    }

    public function isDangerous(string $key): bool
    {
        return (bool) ($this->entries[$key]['dangerous'] ?? false);
    }

    public function groupFor(string $key): string
    {
        return $this->entries[$key]['group'] ?? 'general';
    }

    /** @return class-string */
    public function activityClass(string $key): string
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Unknown flow operation: {$key}");
        }

        return $this->entries[$key]['activity'];
    }

    /** @return array<string, array{label: string, activity: string, configSchema: ?string, icon: ?string, group: string, dangerous: bool}> */
    public function all(): array
    {
        return $this->entries;
    }
}
