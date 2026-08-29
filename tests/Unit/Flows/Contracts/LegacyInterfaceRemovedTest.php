<?php

declare(strict_types=1);
use Flexpik\FilamentStudio\Flows\Operations\FlowOperationActivity;

it('legacy FlowOperationActivity interface is removed', function () {
    expect(interface_exists(FlowOperationActivity::class))->toBeFalse();
});
