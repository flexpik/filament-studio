<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Exceptions\DuplicateFlowOperationException;
use Flexpik\FilamentStudio\Flows\Operations\NoOpActivity;
use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;
use Flexpik\FilamentStudio\Tests\Fixtures\Flows\FakeSlackOperation;

it('registers and resolves operations by key', function () {
    $registry = new OperationRegistry;
    $registry->register('noop', label: 'No-op', activity: NoOpActivity::class);

    expect($registry->has('noop'))->toBeTrue();
    expect($registry->resolve('noop'))->toBeInstanceOf(NoOpActivity::class);
    expect($registry->labelFor('noop'))->toBe('No-op');
});

it('throws on unknown operation', function () {
    (new OperationRegistry)->resolve('does_not_exist');
})->throws(InvalidArgumentException::class, 'does_not_exist');

it('NoOpActivity returns the resolved config', function () {
    $ctx = makeOperationContext(config: ['x' => 1]);
    $result = (new NoOpActivity)->execute($ctx);
    expect($result->output())->toBe(['x' => 1]);
});

it('registers and resolves an operation with fixture', function () {
    $r = new OperationRegistry;
    $r->register('foo', 'Foo', FakeSlackOperation::class);
    expect($r->has('foo'))->toBeTrue();
    expect($r->labelFor('foo'))->toBe('Foo');
    expect($r->resolve('foo'))->toBeInstanceOf(FakeSlackOperation::class);
    expect($r->groupFor('foo'))->toBe('general');
    expect($r->isDangerous('foo'))->toBeFalse();
    expect($r->configFor('foo'))->toBeNull();
});

it('throws DuplicateFlowOperationException on duplicate key', function () {
    $r = new OperationRegistry;
    $r->register('foo', 'Foo', FakeSlackOperation::class);
    $r->register('foo', 'Foo 2', FakeSlackOperation::class);
})->throws(DuplicateFlowOperationException::class, "Flow operation 'foo' is already registered");

it('marks operation as dangerous', function () {
    $r = new OperationRegistry;
    $r->register('danger', 'Danger', FakeSlackOperation::class, dangerous: true);
    expect($r->isDangerous('danger'))->toBeTrue();
});

it('stores icon and group', function () {
    $r = new OperationRegistry;
    $r->register('op', 'Op', FakeSlackOperation::class, icon: 'heroicon-o-test', group: 'mygroup');
    expect($r->groupFor('op'))->toBe('mygroup');
});
