<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Resources\OperatorCatalogResource;
use Laravel\Mcp\Request;

it('lists all 23 filter operators', function () {
    $response = (new OperatorCatalogResource)->handle(new Request);

    $payload = json_decode((string) $response->content(), true);

    expect($payload)->toHaveKeys(['operators', 'filter_group_shape']);
    expect($payload['operators'])->toHaveCount(23);

    foreach ($payload['operators'] as $entry) {
        expect($entry)->toHaveKeys(['key', 'label', 'arity']);
    }

    $keys = array_column($payload['operators'], 'key');
    expect($keys)->toContain('eq', 'gt', 'between', 'in', 'is_null', 'contains_all');
});

it('includes the FilterGroup JSON shape with example', function () {
    $response = (new OperatorCatalogResource)->handle(new Request);

    $payload = json_decode((string) $response->content(), true);

    expect($payload['filter_group_shape'])->toHaveKeys(['description', 'example']);
});
