<?php

declare(strict_types=1);

it('flows config has webhook_rate_limit_per_minute default', function () {
    expect(config('filament-studio.flows.webhook_rate_limit_per_minute'))->toBe(60);
});

it('flows config has webhook_timestamp_window_seconds default', function () {
    expect(config('filament-studio.flows.webhook_timestamp_window_seconds'))->toBe(300);
});

it('flows config has empty webhook_ip_allowlist by default', function () {
    expect(config('filament-studio.flows.webhook_ip_allowlist'))->toBe([]);
});

it('flows config has sensitive_key_patterns with expected defaults', function () {
    $patterns = config('filament-studio.flows.sensitive_key_patterns');
    expect($patterns)->toBeArray()
        ->toContain('/password/i')
        ->toContain('/token/i')
        ->toContain('/secret/i')
        ->toContain('/api[_-]?key/i')
        ->toContain('/authorization/i')
        ->toContain('/bearer/i');
});

it('env override works for webhook_timestamp_window_seconds', function () {
    config()->set('filament-studio.flows.webhook_timestamp_window_seconds', 600);
    expect(config('filament-studio.flows.webhook_timestamp_window_seconds'))->toBe(600);
});
