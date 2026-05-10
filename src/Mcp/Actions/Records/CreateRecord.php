<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Actions\Records;

use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class CreateRecord
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function __invoke(array $input, ?int $tenantId): StudioRecord
    {
        $base = Validator::validate($input, [
            'collection_slug' => ['required', 'string'],
            'data' => ['required', 'array'],
            'locale' => ['nullable', 'string'],
        ]);

        $collection = StudioCollection::query()
            ->forTenant($tenantId)
            ->where('slug', $base['collection_slug'])
            ->first();

        if ($collection === null) {
            throw new StudioNotFoundException('collection', $base['collection_slug']);
        }

        $fields = EavQueryBuilder::getCachedFields($collection);
        $rules = $this->rulesFor($fields, mode: 'store');
        Validator::validate($input, $rules);

        return EavQueryBuilder::for($collection)
            ->locale($base['locale'] ?? null)
            ->create($base['data'] ?? []);
    }

    /**
     * @param  Collection<int, StudioField>  $fields
     * @return array<string, array<string>>
     */
    public function rulesFor(Collection $fields, string $mode): array
    {
        $rules = [];

        /** @var StudioField $field */
        foreach ($fields as $field) {
            if ($field->is_system) {
                continue;
            }

            $base = $mode === 'store'
                ? ($field->is_required ? ['required'] : ['nullable'])
                : ['sometimes'];

            $type = match ($field->eav_cast->value) {
                'integer' => ['integer'],
                'decimal' => ['numeric'],
                'boolean' => ['boolean'],
                'datetime' => ['date'],
                'json' => ['array'],
                default => ['string'],
            };

            $extra = is_array($field->validation_rules) ? $field->validation_rules : [];
            $rules['data.'.$field->column_name] = array_merge($base, $type, $extra);
        }

        return $rules;
    }
}
