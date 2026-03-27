<?php

use Flexpik\FilamentStudio\FilamentStudioPlugin;

it('has API disabled by default', function () {
    $plugin = FilamentStudioPlugin::make();

    expect($plugin->isApiEnabled())->toBeFalse();
});

it('can enable API via fluent method', function () {
    $plugin = FilamentStudioPlugin::make();
    $plugin->enableApi();

    expect($plugin->isApiEnabled())->toBeTrue();
});
