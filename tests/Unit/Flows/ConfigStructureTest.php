<?php

declare(strict_types=1);

it('exposes flows config keys with correct defaults', function () {
    expect(config('filament-studio.flows.enabled'))->toBeFalse();
    expect(config('filament-studio.flows.queue'))->toBe('default');
    expect(config('filament-studio.flows.connection'))->toBeNull();
    expect(config('filament-studio.flows.log_channel'))->toBe('daily');
    expect(config('filament-studio.flows.log_retention_days'))->toBe(30);
    expect(config('filament-studio.flows.max_call_depth'))->toBe(5);
});
