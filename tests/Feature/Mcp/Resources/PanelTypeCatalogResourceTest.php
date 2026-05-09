<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Resources\PanelTypeCatalogResource;
use Laravel\Mcp\Request;

it('lists all 9 panel types with placements', function () {
    $response = (new PanelTypeCatalogResource)->handle(new Request);

    $payload = json_decode((string) $response->content(), true);

    expect($payload)->toHaveKey('panel_types');
    expect($payload['panel_types'])->toHaveCount(9);

    foreach ($payload['panel_types'] as $entry) {
        expect($entry)->toHaveKeys(['key', 'label', 'description', 'placements']);
        expect($entry['placements'])->toBeArray();
    }
});
