<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Resources\FieldTypeCatalogResource;
use Laravel\Mcp\Request;

it('lists all 33 field types with key, label, category, eav_cast', function () {
    $response = (new FieldTypeCatalogResource)->handle(new Request);

    $payload = json_decode((string) $response->content(), true);

    expect($payload)->toHaveKey('field_types');
    expect($payload['field_types'])->toHaveCount(33);

    foreach ($payload['field_types'] as $entry) {
        expect($entry)->toHaveKeys(['key', 'label', 'category', 'eav_cast']);
    }

    $keys = array_column($payload['field_types'], 'key');
    expect($keys)->toContain('text', 'integer', 'select', 'belongs_to');
});
