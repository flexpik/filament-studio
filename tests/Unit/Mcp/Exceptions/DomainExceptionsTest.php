<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Mcp\Exceptions\ConfirmTokenInvalidException;
use Flexpik\FilamentStudio\Mcp\Exceptions\EavCastConflictException;
use Flexpik\FilamentStudio\Mcp\Exceptions\IntegrityException;
use Flexpik\FilamentStudio\Mcp\Exceptions\StudioNotFoundException;

it('expired token uses EXPIRED_CONFIRM_TOKEN code', function () {
    $e = ConfirmTokenInvalidException::expired('ct_abc');
    expect($e->mcpCode)->toBe('EXPIRED_CONFIRM_TOKEN')
        ->and($e->mcpData())->toMatchArray(['next_action' => 'call preview_* again']);
});

it('mismatched token uses INVALID_CONFIRM_TOKEN code', function () {
    expect(ConfirmTokenInvalidException::mismatched('ct_abc')->mcpCode)
        ->toBe('INVALID_CONFIRM_TOKEN');
});

it('consumed token uses CONSUMED_CONFIRM_TOKEN code', function () {
    expect(ConfirmTokenInvalidException::consumed('ct_abc')->mcpCode)
        ->toBe('CONSUMED_CONFIRM_TOKEN');
});

it('eav cast conflict carries value count and suggested action', function () {
    $e = new EavCastConflictException(field: 'price', from: 'decimal', to: 'text', valueCount: 2417);
    expect($e->mcpCode())->toBe('STUDIO_EAV_CAST_CONFLICT')
        ->and($e->mcpData())->toMatchArray([
            'field' => 'price',
            'from_cast' => 'decimal',
            'to_cast' => 'text',
            'value_count' => 2417,
        ])
        ->and($e->mcpData()['suggested_action'])->toContain('Create a new field');
});

it('integrity violation carries field and conflicting value', function () {
    $e = IntegrityException::duplicate(field: 'slug', value: 'products');
    expect($e->mcpCode())->toBe('STUDIO_INTEGRITY_VIOLATION')
        ->and($e->mcpData())->toMatchArray(['field' => 'slug', 'conflicting_value' => 'products']);
});

it('not-found carries resource type and identifier', function () {
    $e = new StudioNotFoundException(resource: 'collection', identifier: 'products');
    expect($e->mcpCode())->toBe('STUDIO_NOT_FOUND')
        ->and($e->mcpData())->toMatchArray(['resource' => 'collection', 'identifier' => 'products']);
});
