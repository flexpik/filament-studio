<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Contracts\Flows\ConfigFieldType;
use Flexpik\FilamentStudio\Contracts\Flows\FlowOperationConfig;
use Flexpik\FilamentStudio\Flows\Exceptions\InvalidOperationConfigException;
use Flexpik\FilamentStudio\Flows\Operations\Validation\ConfigSchemaValidator;

it('rejects a config schema referencing an unknown field type', function () {
    $bad = new class implements FlowOperationConfig
    {
        public function schema(): array
        {
            return ['fields' => [['name' => 'x', 'type' => 'magic']]];
        }

        public function defaults(): array
        {
            return [];
        }

        public function validate(array $config): void {}
    };

    ConfigSchemaValidator::validate($bad);
})->throws(InvalidOperationConfigException::class, "Unknown field type 'magic'");

it('accepts schemas using all 9 catalog types', function () {
    foreach (ConfigFieldType::cases() as $type) {
        $schema = new class($type) implements FlowOperationConfig
        {
            public function __construct(public ConfigFieldType $t) {}

            public function schema(): array
            {
                return ['fields' => [['name' => 'x', 'type' => $this->t->value]]];
            }

            public function defaults(): array
            {
                return [];
            }

            public function validate(array $config): void {}
        };
        expect(fn () => ConfigSchemaValidator::validate($schema))->not->toThrow(Throwable::class);
    }
});

it('accepts schemas with no fields array (empty schema)', function () {
    $empty = new class implements FlowOperationConfig
    {
        public function schema(): array
        {
            return [];
        }

        public function defaults(): array
        {
            return [];
        }

        public function validate(array $config): void {}
    };
    expect(fn () => ConfigSchemaValidator::validate($empty))->not->toThrow(Throwable::class);
});
