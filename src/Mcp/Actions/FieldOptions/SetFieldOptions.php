<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\FieldOptions;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioFieldOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SetFieldOptions
{
    /** @var array<string> Options-supporting field type keys */
    private const OPTIONS_TYPES = ['select', 'multi_select', 'checkbox_list', 'radio'];

    /**
     * @param  array<int, array<string, mixed>>  $options
     * @return array<int, StudioFieldOption>
     */
    public function __invoke(string $collectionSlug, string $columnName, array $options, ?int $tenantId): array
    {
        Validator::validate(['options' => $options], [
            'options' => ['required', 'array'],
            'options.*.value' => ['required', 'string'],
            'options.*.label' => ['required', 'string'],
            'options.*.color' => ['nullable', 'string'],
            'options.*.icon' => ['nullable', 'string'],
            'options.*.sort_order' => ['nullable', 'integer'],
        ]);

        $collection = StudioCollection::query()
            ->forTenant($tenantId)
            ->where('slug', $collectionSlug)
            ->first();

        if ($collection === null) {
            throw new StudioNotFoundException('collection', $collectionSlug);
        }

        $field = StudioField::query()
            ->where('collection_id', $collection->id)
            ->where('column_name', $columnName)
            ->first();

        if ($field === null) {
            throw new StudioNotFoundException('field', "{$collectionSlug}.{$columnName}");
        }

        if (! in_array($field->field_type, self::OPTIONS_TYPES, true)) {
            throw ValidationException::withMessages([
                'field_type' => ["Field type '{$field->field_type}' does not support options."],
            ]);
        }

        return DB::transaction(function () use ($field, $tenantId, $options) {
            $field->options()->delete();
            $created = [];
            foreach ($options as $i => $opt) {
                $created[] = StudioFieldOption::create([
                    'field_id' => $field->id,
                    'tenant_id' => $tenantId,
                    'value' => $opt['value'],
                    'label' => $opt['label'],
                    'color' => $opt['color'] ?? null,
                    'icon' => $opt['icon'] ?? null,
                    'sort_order' => $opt['sort_order'] ?? $i,
                ]);
            }

            return $created;
        });
    }
}
