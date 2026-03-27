<?php

namespace Flexpik\FilamentStudio\Filtering;

final class FilterGroup implements FilterNode
{
    /**
     * @param  'and'|'or'  $logic
     * @param  array<FilterNode>  $children
     */
    public function __construct(
        public string $logic,
        public array $children = [],
    ) {}

    /**
     * Parse a nested array into a FilterGroup tree.
     *
     * @param  array{logic: string, rules: array}  $data
     */
    public static function fromArray(array $data): self
    {
        $children = [];

        foreach ($data['rules'] as $item) {
            if (isset($item['logic'])) {
                $children[] = self::fromArray($item);
            } else {
                $children[] = FilterRule::fromArray($item);
            }
        }

        return new self(
            logic: $data['logic'],
            children: $children,
        );
    }

    public static function empty(): self
    {
        return new self(logic: 'and', children: []);
    }

    public function toArray(): array
    {
        return [
            'logic' => $this->logic,
            'rules' => array_map(
                fn (FilterNode $child) => $child->toArray(),
                $this->children,
            ),
        ];
    }

    public function isEmpty(): bool
    {
        return count($this->children) === 0;
    }
}
