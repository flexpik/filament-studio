<?php

namespace Flexpik\FilamentStudio\FieldTypes;

use Flexpik\FilamentStudio\Models\StudioField;
use InvalidArgumentException;

class FieldTypeRegistry
{
    /**
     * Registered field type classes keyed by their static $key.
     *
     * @var array<string, class-string<AbstractFieldType>>
     */
    protected array $types = [];

    /**
     * Custom value transformers keyed by field type key.
     *
     * @var array<string, array{serialize: callable, deserialize: callable}>
     */
    protected array $transformers = [];

    /**
     * Register a field type class.
     *
     * @param  class-string<AbstractFieldType>  $class
     */
    public function register(string $class): void
    {
        $this->types[$class::$key] = $class;
    }

    /**
     * Resolve a StudioField to its AbstractFieldType instance.
     */
    public function make(StudioField $field): AbstractFieldType
    {
        $key = $field->field_type;

        if (! isset($this->types[$key])) {
            throw new InvalidArgumentException(
                "Field type [{$key}] is not registered. Available types: ".implode(', ', array_keys($this->types))
            );
        }

        $class = $this->types[$key];

        return new $class($field);
    }

    /**
     * Get all registered field type classes keyed by their key.
     *
     * @return array<string, class-string<AbstractFieldType>>
     */
    public function all(): array
    {
        return $this->types;
    }

    /**
     * Get registered field types grouped by category.
     *
     * @return array<string, list<class-string<AbstractFieldType>>>
     */
    public function categories(): array
    {
        $grouped = [];

        foreach ($this->types as $class) {
            $grouped[$class::$category][] = $class;
        }

        return $grouped;
    }

    /**
     * Register a custom value transformer for a field type.
     */
    public function registerTransformer(
        string $fieldTypeKey,
        callable $serialize,
        callable $deserialize,
    ): void {
        $this->transformers[$fieldTypeKey] = [
            'serialize' => $serialize,
            'deserialize' => $deserialize,
        ];
    }

    /**
     * Get the transformer for a field type, or null if none registered.
     *
     * @return array{serialize: callable, deserialize: callable}|null
     */
    public function getTransformer(string $fieldTypeKey): ?array
    {
        return $this->transformers[$fieldTypeKey] ?? null;
    }
}
