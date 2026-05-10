<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Resources\PanelTypeDetailResource;
use Laravel\Mcp\Request;

it('returns config_schema for a known panel type', function () {
    $response = (new PanelTypeDetailResource)->handle(
        new Request(['key' => 'metric'])
    );

    $payload = json_decode((string) $response->content(), true);

    expect($payload)->toMatchArray(['key' => 'metric']);
    expect($payload)->toHaveKeys(['key', 'label', 'config_schema', 'placements']);
    expect($payload['config_schema'])->toHaveKey('type', 'object');
});

it('returns STUDIO_NOT_FOUND for unknown key', function () {
    $response = (new PanelTypeDetailResource)->handle(
        new Request(['key' => 'no_such_panel'])
    );

    $payload = json_decode((string) $response->content(), true);

    expect($payload['error']['code'])->toBe('STUDIO_NOT_FOUND');
});
