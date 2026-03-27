<?php

namespace Flexpik\FilamentStudio\Panels;

use Filament\Schemas\Components\Component;
use Flexpik\FilamentStudio\Enums\PanelPlacement;
use InvalidArgumentException;

class PanelTypeRegistry
{
    /**
     * Registered panel type classes keyed by their static $key.
     *
     * @var array<string, class-string<AbstractStudioPanel>>
     */
    protected array $types = [];

    /**
     * Register a panel type class.
     *
     * @param  class-string<AbstractStudioPanel>  $class
     */
    public function register(string $class): void
    {
        if (! is_subclass_of($class, AbstractStudioPanel::class)) {
            throw new InvalidArgumentException(
                "Panel type [{$class}] must extend ".AbstractStudioPanel::class
            );
        }

        $this->types[$class::$key] = $class;
    }

    /**
     * Get a registered panel type class by key.
     *
     * @return class-string<AbstractStudioPanel>
     */
    public function get(string $key): string
    {
        if (! isset($this->types[$key])) {
            throw new InvalidArgumentException(
                "Panel type [{$key}] is not registered. Available: ".implode(', ', array_keys($this->types))
            );
        }

        return $this->types[$key];
    }

    /**
     * Get all registered panel type classes keyed by their key.
     *
     * @return array<string, class-string<AbstractStudioPanel>>
     */
    public function all(): array
    {
        return $this->types;
    }

    /**
     * Get the config schema for a panel type.
     *
     * @return array<Component>
     */
    public function configSchema(string $key): array
    {
        return $this->get($key)::configSchema();
    }

    /**
     * Get panel types that support a given placement.
     *
     * @return array<string, class-string<AbstractStudioPanel>>
     */
    public function forPlacement(PanelPlacement $placement): array
    {
        return array_filter(
            $this->types,
            fn (string $class) => $class::supportsPlacement($placement),
        );
    }
}
