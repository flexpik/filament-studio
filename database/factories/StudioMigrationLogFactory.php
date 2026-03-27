<?php

namespace Flexpik\FilamentStudio\Database\Factories;

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioMigrationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudioMigrationLog>
 */
class StudioMigrationLogFactory extends Factory
{
    protected $model = StudioMigrationLog::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'collection_id' => StudioCollection::factory(),
            'field_id' => null,
            'operation' => $this->faker->randomElement([
                'create_collection', 'update_collection', 'delete_collection',
                'add_field', 'update_field', 'rename_field', 'delete_field', 'reorder_fields',
            ]),
            'before_state' => null,
            'after_state' => null,
            'performed_by' => null,
            'created_at' => now(),
        ];
    }
}
