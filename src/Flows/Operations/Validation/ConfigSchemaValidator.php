<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Operations\Validation;

use Flexpik\FilamentStudio\Contracts\Flows\ConfigFieldType;
use Flexpik\FilamentStudio\Contracts\Flows\FlowOperationConfig;
use Flexpik\FilamentStudio\Contracts\Flows\FlowTriggerConfig;
use Flexpik\FilamentStudio\Flows\Exceptions\InvalidOperationConfigException;

class ConfigSchemaValidator
{
    private static ?array $validTypes = null;

    /** @param FlowOperationConfig|FlowTriggerConfig $config */
    public static function validate(object $config): void
    {
        $schema = $config->schema();
        $fields = $schema['fields'] ?? [];

        if (! is_array($fields)) {
            return;
        }

        $validTypes = self::getValidTypes();

        foreach ($fields as $field) {
            $type = $field['type'] ?? null;
            if ($type !== null && ! in_array($type, $validTypes, true)) {
                throw new InvalidOperationConfigException(
                    "Unknown field type '{$type}'",
                    ['type' => "'{$type}' is not a valid ConfigFieldType"],
                );
            }
        }
    }

    /** @return string[] */
    private static function getValidTypes(): array
    {
        if (self::$validTypes === null) {
            self::$validTypes = array_map(fn (ConfigFieldType $t) => $t->value, ConfigFieldType::cases());
        }

        return self::$validTypes;
    }
}
