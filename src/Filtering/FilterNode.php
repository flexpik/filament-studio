<?php

namespace Flexpik\FilamentStudio\Filtering;

interface FilterNode
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
