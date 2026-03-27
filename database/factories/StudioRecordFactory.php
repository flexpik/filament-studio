<?php

namespace Flexpik\FilamentStudio\Database\Factories;

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StudioRecord>
 */
class StudioRecordFactory extends Factory
{
    protected $model = StudioRecord::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'collection_id' => StudioCollection::factory(),
            'tenant_id' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function forTenant(int $tenantId): static
    {
        return $this->state(fn () => ['tenant_id' => $tenantId]);
    }
}
