<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Resources\FieldTypeDetailResource;
use Laravel\Mcp\Request;

it('returns settings_schema for a known field type key', function () {
    // Pass key via constructor arguments (URI-template substitution simulation).
    $request = new Request(['key' => 'text']);

    $response = (new FieldTypeDetailResource)->handle($request);

    $payload = json_decode((string) $response->content(), true);

    expect($payload)->toMatchArray(['key' => 'text']);
    expect($payload)->toHaveKeys(['key', 'label', 'category', 'eav_cast', 'settings_schema']);
    expect($payload['settings_schema'])->toHaveKey('type', 'object');
});

it('returns an error payload for an unknown field type key', function () {
    $request = new Request(['key' => 'no_such_type']);

    $response = (new FieldTypeDetailResource)->handle($request);

    $payload = json_decode((string) $response->content(), true);

    expect($payload['error']['code'])->toBe('STUDIO_NOT_FOUND');
});
