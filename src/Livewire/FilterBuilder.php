<?php

namespace Flexpik\FilamentStudio\Livewire;

use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\Enums\FilterOperator;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class FilterBuilder extends Component
{
    #[Locked]
    public int $collectionId;

    /** @var array{logic: string, rules: array} */
    public array $tree = ['logic' => 'and', 'rules' => []];

    public function mount(int $collectionId, ?array $initialTree = null): void
    {
        $this->collectionId = $collectionId;

        if ($initialTree !== null) {
            $this->tree = $initialTree;
        }
    }

    public function addRule(string $path = ''): void
    {
        $newRule = ['field' => '', 'operator' => 'eq', 'value' => ''];

        if ($path === '') {
            $this->tree['rules'][] = $newRule;
        } else {
            $parentRules = data_get($this->tree, "rules.{$path}.rules", []);
            $parentRules[] = $newRule;
            data_set($this->tree, "rules.{$path}.rules", $parentRules);
        }
    }

    public function addGroup(string $path = ''): void
    {
        $newGroup = ['logic' => 'and', 'rules' => []];

        if ($path === '') {
            $this->tree['rules'][] = $newGroup;
        } else {
            $parentRules = data_get($this->tree, "rules.{$path}.rules", []);
            $parentRules[] = $newGroup;
            data_set($this->tree, "rules.{$path}.rules", $parentRules);
        }
    }

    public function removeRule(string $path): void
    {
        $parts = explode('.', $path);
        $index = (int) array_pop($parts);

        if (empty($parts)) {
            array_splice($this->tree['rules'], $index, 1);
            $this->tree['rules'] = array_values($this->tree['rules']);
        } else {
            $parentPath = 'rules.'.implode('.rules.', $parts).'.rules';
            $rules = data_get($this->tree, $parentPath, []);
            array_splice($rules, $index, 1);
            data_set($this->tree, $parentPath, array_values($rules));
        }
    }

    public function toggleLogic(string $path): void
    {
        if ($path === '') {
            $this->tree['logic'] = $this->tree['logic'] === 'and' ? 'or' : 'and';
        } else {
            $currentLogic = data_get($this->tree, "rules.{$path}.logic", 'and');
            data_set($this->tree, "rules.{$path}.logic", $currentLogic === 'and' ? 'or' : 'and');
        }
    }

    /**
     * Return operators valid for a given field's EAV cast.
     *
     * @return array<string, string>
     */
    public function getOperatorsForField(string $fieldName): array
    {
        $collection = StudioCollection::findOrFail($this->collectionId);
        $fields = EavQueryBuilder::getCachedFields($collection);
        $field = $fields->firstWhere('column_name', $fieldName);

        if (! $field) {
            return [];
        }

        return FilterOperator::labelsForCast($field->eav_cast);
    }

    /**
     * Return the EAV cast for a given field.
     */
    public function getCastForField(string $fieldName): ?EavCast
    {
        $collection = StudioCollection::findOrFail($this->collectionId);
        $fields = EavQueryBuilder::getCachedFields($collection);
        $field = $fields->firstWhere('column_name', $fieldName);

        return $field?->eav_cast;
    }

    /**
     * Return the filterable fields for the component.
     *
     * @return array<string, string>
     */
    public function getFieldOptions(): array
    {
        $collection = StudioCollection::findOrFail($this->collectionId);
        $fields = EavQueryBuilder::getCachedFields($collection);

        return $fields
            ->where('is_filterable', true)
            ->mapWithKeys(fn ($field) => [$field->column_name => $field->label ?? $field->column_name])
            ->all();
    }

    public function applyFilter(): void
    {
        $this->dispatch('filter-applied', tree: $this->tree);
    }

    public function clearFilter(): void
    {
        $this->tree = ['logic' => 'and', 'rules' => []];
        $this->dispatch('filter-applied', tree: $this->tree);
    }

    public function render(): View
    {
        return view('filament-studio::livewire.filter-builder', [
            'fieldOptions' => $this->getFieldOptions(),
        ]);
    }
}
