<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Exceptions\EavCastConflictException;
use Flexpik\FilamentStudio\Mcp\Exceptions\IntegrityException;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpAuthorizationException;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioMcpExceptionHandler;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->handler = new StudioMcpExceptionHandler);

it('maps authorization exceptions', function () {
    $e = new StudioMcpAuthorizationException(
        'API key lacks required scope.',
        ['required_scope' => '_studio.manage_collections', 'granted_scopes' => []]
    );

    $response = $this->handler->toResponse($e);

    expect($response['code'])->toBe('STUDIO_UNAUTHORIZED')
        ->and($response['data']['required_scope'])->toBe('_studio.manage_collections');
});

it('maps EAV cast conflicts', function () {
    $response = $this->handler->toResponse(
        new EavCastConflictException('price', 'decimal', 'text', 2417)
    );

    expect($response['code'])->toBe('STUDIO_EAV_CAST_CONFLICT')
        ->and($response['data']['value_count'])->toBe(2417);
});

it('maps validation exceptions with errors payload', function () {
    $response = $this->handler->toResponse(ValidationException::withMessages([
        'name' => ['Name is required.'],
    ]));

    expect($response['code'])->toBe('STUDIO_VALIDATION_FAILED')
        ->and($response['data']['errors'])->toHaveKey('name');
});

it('maps not-found exceptions', function () {
    $response = $this->handler->toResponse(new StudioNotFoundException('collection', 'products'));

    expect($response['code'])->toBe('STUDIO_NOT_FOUND')
        ->and($response['data']['identifier'])->toBe('products');
});

it('maps integrity exceptions', function () {
    $response = $this->handler->toResponse(IntegrityException::duplicate('slug', 'products'));

    expect($response['code'])->toBe('STUDIO_INTEGRITY_VIOLATION')
        ->and($response['data']['field'])->toBe('slug');
});

it('logs unexpected exceptions and returns a correlation id', function () {
    Log::spy();

    $response = $this->handler->toResponse(new RuntimeException('boom'));

    expect($response['code'])->toBe('STUDIO_INTERNAL_ERROR')
        ->and($response['data']['correlation_id'])->toBeString()
        ->and(strlen($response['data']['correlation_id']))->toBeGreaterThan(8);

    Log::shouldHaveReceived('error')->once();
});
