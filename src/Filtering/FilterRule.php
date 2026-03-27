<?php

namespace Flexpik\FilamentStudio\Filtering;

use Flexpik\FilamentStudio\Enums\FilterOperator;

final readonly class FilterRule implements FilterNode
{
    public function __construct(
        public string $field,
        public FilterOperator $operator,
        public mixed $value = null,
        public ?string $relatedField = null,
    ) {}

    /**
     * @param  array{field: string, operator: string, value?: mixed, related_field?: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            field: $data['field'],
            operator: FilterOperator::from($data['operator']),
            value: $data['value'] ?? null,
            relatedField: $data['related_field'] ?? null,
        );
    }

    public function toArray(): array
    {
        $result = [
            'field' => $this->field,
            'operator' => $this->operator->value,
            'value' => $this->value,
        ];

        if ($this->relatedField !== null) {
            $result['related_field'] = $this->relatedField;
        }

        return $result;
    }

    /**
     * Check if the value is a dynamic variable token ($CURRENT_USER, $NOW, etc.).
     */
    public function hasDynamicValue(): bool
    {
        if (! is_string($this->value)) {
            return false;
        }

        return str_starts_with($this->value, '$');
    }

    public function isRelational(): bool
    {
        return $this->relatedField !== null;
    }
}
