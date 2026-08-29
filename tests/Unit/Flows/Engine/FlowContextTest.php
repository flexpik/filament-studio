<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Engine\FlowContext;

it('exposes trigger, last, accountability, and per-key outputs', function () {
    $ctx = FlowContext::make(
        trigger: ['email' => 'a@b.com'],
        accountability: ['user_id' => '1', 'tenant_id' => 't1', 'role' => null, 'source' => 'manual'],
    );

    expect($ctx->trigger())->toBe(['email' => 'a@b.com']);
    expect($ctx->accountability())->toMatchArray(['tenant_id' => 't1']);
    expect($ctx->last())->toBeNull();

    $ctx->set('create_user', ['id' => 42]);
    expect($ctx->get('create_user'))->toBe(['id' => 42]);
    expect($ctx->last())->toBe(['id' => 42]);
});

it('toArray exposes the variable bag for templating', function () {
    $ctx = FlowContext::make(trigger: ['x' => 1]);
    $ctx->set('op_a', ['y' => 2]);

    $bag = $ctx->toArray();
    expect($bag['$trigger'])->toBe(['x' => 1]);
    expect($bag['$last'])->toBe(['y' => 2]);
    expect($bag['op_a'])->toBe(['y' => 2]);
    expect($bag)->toHaveKey('$accountability');
});
