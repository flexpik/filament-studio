<?php

namespace Flexpik\FilamentStudio\Rules;

use Closure;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioValue;
use Illuminate\Contracts\Validation\ValidationRule;

class EavUniqueRule implements ValidationRule
{
    public function __construct(
        protected StudioField $field,
        protected StudioCollection $collection,
        protected ?int $tenantId = null,
        protected ?int $ignoreRecordId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cast = $this->field->eav_cast;
        $column = $cast->column();

        $exists = StudioValue::query()
            ->where('field_id', $this->field->id)
            ->where($column, $value)
            ->whereHas('record', fn ($q) => $q
                ->where('collection_id', $this->collection->id)
                ->where('tenant_id', $this->tenantId)
                ->whereNull('deleted_at')
                ->when($this->ignoreRecordId, fn ($q) => $q
                    ->where('id', '!=', $this->ignoreRecordId)
                )
            )
            ->exists();

        if ($exists) {
            $fail("The {$attribute} has already been taken.");
        }
    }
}
