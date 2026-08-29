<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Contracts\Flows\DataChain;
use Flexpik\FilamentStudio\Contracts\Flows\OperationContext;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;

it('exposes accessors and is immutable', function () {
    $flow = StudioFlow::factory()->create();
    $run = StudioFlowRun::factory()->for($flow, 'flow')->create();
    $chain = new DataChain(
        trigger: ['email' => 'a@b.test'],
        outputs: ['fetch_user' => ['id' => 42]],
        last: ['id' => 42],
    );

    $ctx = new OperationContext(
        flow: $flow,
        run: $run,
        dataChain: $chain,
        config: ['to' => 'a@b.test'],
        tenantId: 'tenant-1',
    );

    expect($ctx->flow()->is($flow))->toBeTrue();
    expect($ctx->run()->is($run))->toBeTrue();
    expect($ctx->tenantId())->toBe('tenant-1');
    expect($ctx->config())->toBe(['to' => 'a@b.test']);
    expect($ctx->dataChain()->trigger())->toBe(['email' => 'a@b.test']);
    expect($ctx->dataChain()->last())->toBe(['id' => 42]);
    expect($ctx->dataChain()->get('fetch_user'))->toBe(['id' => 42]);
    expect($ctx->dataChain()->get('missing'))->toBeNull();
});

it('interpolates references against the data chain', function () {
    $ctx = makeOperationContext(
        trigger: ['user' => ['name' => 'Ada']],
        outputs: ['load' => ['email' => 'ada@lovelace.dev']],
    );

    expect($ctx->interpolate('Hello {{ $trigger.user.name }}'))
        ->toBe('Hello Ada');
    expect($ctx->interpolate('{{ $load.email }}'))
        ->toBe('ada@lovelace.dev');
});

it('returns empty string for unknown reference in interpolate', function () {
    $ctx = makeOperationContext(
        trigger: ['name' => 'Ada'],
        outputs: [],
    );

    expect($ctx->interpolate('{{ $trigger.missing_key }}'))->toBe('');
    expect($ctx->interpolate('{{ $nonexistent }}'))->toBe('');
});

it('returns json_encode for non-scalar interpolation', function () {
    $ctx = makeOperationContext(
        trigger: [],
        outputs: ['step' => ['items' => [1, 2, 3]]],
    );

    expect($ctx->interpolate('{{ step.items }}'))
        ->toBe(json_encode([1, 2, 3], JSON_UNESCAPED_SLASHES));
});

it('readonly properties prevent mutation', function () {
    $ctx = makeOperationContext();
    $rc = new ReflectionClass($ctx);
    foreach ($rc->getProperties() as $prop) {
        expect($prop->isReadOnly())->toBeTrue();
    }
});
