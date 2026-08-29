<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Enums\StudioPermission;

it('exposes the five flow permission cases', function () {
    foreach (['ViewFlows', 'CreateFlows', 'UpdateFlows', 'DeleteFlows', 'RunFlows'] as $name) {
        expect(defined(StudioPermission::class.'::'.$name))->toBeTrue("missing case {$name}");
    }
    expect(StudioPermission::ViewFlows->value)->toBe('view_flows');
});
