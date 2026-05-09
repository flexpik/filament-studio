<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Support;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;

class FilamentSchemaToJsonSchema
{
    /**
     * @param  array<int, mixed>  $components
     * @return array<string, mixed>
     */
    public function translate(array $components): array
    {
        $properties = [];
        $required = [];

        foreach ($components as $component) {
            if (! $component instanceof Field) {
                continue;
            }

            $name = $component->getName();
            $entry = $this->translateOne($component);
            $properties[$name] = $entry;

            if (method_exists($component, 'isRequired') && $component->isRequired()) {
                $required[] = $name;
            }
        }

        $out = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if ($required !== []) {
            $out['required'] = $required;
        }

        return $out;
    }

    /** @return array<string, mixed> */
    protected function translateOne(Field $component): array
    {
        return match (true) {
            $component instanceof TextInput, $component instanceof Textarea => $this->translateString($component),
            $component instanceof Toggle, $component instanceof Checkbox => $this->translateBoolean($component),
            $component instanceof Select => $this->translateSelect($component),
            $component instanceof DatePicker => ['type' => 'string', 'format' => 'date'],
            $component instanceof DateTimePicker => ['type' => 'string', 'format' => 'date-time'],
            $component instanceof TimePicker => ['type' => 'string', 'format' => 'time'],
            default => $this->fallback(),
        };
    }

    /** @return array<string, mixed> */
    protected function translateString(Field $component): array
    {
        $entry = ['type' => 'string'];

        if (method_exists($component, 'getMaxLength')) {
            $max = $component->getMaxLength();
            if (is_int($max)) {
                $entry['maxLength'] = $max;
            }
        }

        if (method_exists($component, 'getDefaultState')) {
            $default = $component->getDefaultState();
            if ($default !== null) {
                $entry['default'] = $default;
            }
        }

        return $entry;
    }

    /** @return array<string, mixed> */
    protected function translateBoolean(Field $component): array
    {
        $entry = ['type' => 'boolean'];

        if (method_exists($component, 'getDefaultState')) {
            $default = $component->getDefaultState();
            if (is_bool($default)) {
                $entry['default'] = $default;
            }
        }

        return $entry;
    }

    /** @return array<string, mixed> */
    protected function translateSelect(Select $component): array
    {
        $entry = ['type' => 'string'];

        $options = $component->getOptions();
        if (is_array($options) && $options !== []) {
            $entry['enum'] = array_keys($options);
        }

        return $entry;
    }

    /** @return array<string, mixed> */
    protected function fallback(): array
    {
        return [
            'type' => 'unknown',
            'description' => 'This setting is configurable in the Filament UI but is not represented in JSON Schema. Refer to the field-type documentation for valid values.',
        ];
    }
}
