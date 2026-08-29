<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Policies\FlowPolicy;

it('viewAny respects the view_flows permission', function () {
    $user = $this->makeUserWith(['view_flows']);
    expect((new FlowPolicy)->viewAny($user))->toBeTrue();

    $other = $this->makeUserWith([]);
    expect((new FlowPolicy)->viewAny($other))->toBeFalse();
});

it('create respects create_flows', function () {
    $user = $this->makeUserWith(['create_flows']);
    expect((new FlowPolicy)->create($user))->toBeTrue();
});

it('run respects run_flows', function () {
    $user = $this->makeUserWith(['run_flows']);
    $flow = StudioFlow::factory()->create();
    expect((new FlowPolicy)->run($user, $flow))->toBeTrue();
});
