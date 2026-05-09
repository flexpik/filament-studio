<?php

declare(strict_types=1);

it('exposes mcp config keys with correct defaults', function () {
    expect(config('filament-studio.mcp.enabled'))->toBeFalse();
    expect(config('filament-studio.mcp.http.enabled'))->toBeTrue();
    expect(config('filament-studio.mcp.http.prefix'))->toBe('ai/studio');
    expect(config('filament-studio.mcp.http.rate_limit'))->toBe(120);
    expect(config('filament-studio.mcp.stdio.enabled'))->toBeTrue();
    expect(config('filament-studio.mcp.stdio.handle'))->toBe('studio');
    expect(config('filament-studio.mcp.confirm_token_ttl'))->toBe(300);
    expect(config('filament-studio.mcp.limits.query_max_per_page'))->toBe(100);
    expect(config('filament-studio.mcp.limits.query_max_filter_depth'))->toBe(5);
    expect(config('filament-studio.mcp.limits.create_collection_max_fields'))->toBe(50);
    expect(config('filament-studio.mcp.logging.log_requests'))->toBeTrue();
    expect(config('filament-studio.mcp.logging.log_errors'))->toBeTrue();
});
