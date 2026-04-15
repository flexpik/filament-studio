<?php

it('has locales config section with defaults', function () {
    $config = config('filament-studio.locales');

    expect($config)->toBeArray()
        ->and($config['enabled'])->toBeFalse()
        ->and($config['available'])->toBe(['en'])
        ->and($config['default'])->toBe('en');
});
