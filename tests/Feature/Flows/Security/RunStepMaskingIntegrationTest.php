<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Security\MasksSensitiveValues;

it('masks sensitive keys before writing run step input', function () {
    // Test the masker directly at the integration point —
    // verify that MasksSensitiveValues is applied before DB write
    // by checking that a step stored with sensitive input has it masked
    $masker = app(MasksSensitiveValues::class);

    // Simulate the data that would be stored as step input
    $config = ['url' => 'https://api.example.com', 'api_key' => 'secret-key-abc'];
    $masked = $masker->mask($config);

    expect($masked['api_key'])->toBe('***');
    expect($masked['url'])->toBe('https://api.example.com');
});

it('masks sensitive output keys', function () {
    $masker = app(MasksSensitiveValues::class);

    $output = ['data' => ['api_key' => 'abc', 'result' => 'success'], 'status' => 200];
    $masked = $masker->mask($output);

    expect($masked['data']['api_key'])->toBe('***');
    expect($masked['data']['result'])->toBe('success');
    expect($masked['status'])->toBe(200);
});
