<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Exceptions\DuplicateFlowTriggerException;
use Flexpik\FilamentStudio\Flows\Triggers\TriggerRegistry;
use Flexpik\FilamentStudio\Tests\Fixtures\Flows\FakeStripeTrigger;

it('registers and resolves a trigger', function () {
    $r = new TriggerRegistry;
    $r->register('stripe', 'Stripe', FakeStripeTrigger::class);
    expect($r->has('stripe'))->toBeTrue();
    expect($r->resolve('stripe'))->toBeInstanceOf(FakeStripeTrigger::class);
});

it('throws on unknown trigger', function () {
    (new TriggerRegistry)->resolve('nope');
})->throws(InvalidArgumentException::class);

it('throws DuplicateFlowTriggerException on duplicate key', function () {
    $r = new TriggerRegistry;
    $r->register('stripe', 'Stripe', FakeStripeTrigger::class);
    $r->register('stripe', 'Stripe 2', FakeStripeTrigger::class);
})->throws(DuplicateFlowTriggerException::class, "Flow trigger 'stripe' is already registered");
