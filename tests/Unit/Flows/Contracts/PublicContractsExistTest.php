<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\FlowOperationConfig;
use Flexpik\FilamentStudio\Contracts\Flows\FlowTrigger as PublicFlowTrigger;
use Flexpik\FilamentStudio\Contracts\Flows\FlowTriggerConfig;

it('exposes FlowOperation public contract', function () {
    expect(interface_exists(FlowOperation::class))->toBeTrue();
    $rc = new ReflectionClass(FlowOperation::class);
    expect($rc->hasMethod('execute'))->toBeTrue();
    $m = $rc->getMethod('execute');
    expect($m->getNumberOfParameters())->toBe(1);
    expect((string) $m->getReturnType())->toContain('OperationResult');
});

it('exposes FlowOperationConfig public contract', function () {
    $rc = new ReflectionClass(FlowOperationConfig::class);
    foreach (['schema', 'defaults', 'validate'] as $method) {
        expect($rc->hasMethod($method))->toBeTrue();
    }
});

it('exposes FlowTrigger public contract with register/unregister', function () {
    $rc = new ReflectionClass(PublicFlowTrigger::class);
    expect($rc->hasMethod('register'))->toBeTrue();
    expect($rc->hasMethod('unregister'))->toBeTrue();
});

it('exposes FlowTriggerConfig public contract', function () {
    expect(interface_exists(FlowTriggerConfig::class))->toBeTrue();
});
