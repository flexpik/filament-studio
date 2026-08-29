<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Contracts\Flows\OperationResult;

it('constructs a success result', function () {
    $r = OperationResult::success(['id' => 1]);
    expect($r->isSuccess())->toBeTrue();
    expect($r->isFailure())->toBeFalse();
    expect($r->output())->toBe(['id' => 1]);
    expect($r->branch())->toBeNull();
});

it('constructs a failure result preserving previous throwable', function () {
    $e = new RuntimeException('nope');
    $r = OperationResult::fail('something broke', $e);
    expect($r->isFailure())->toBeTrue();
    expect($r->message())->toBe('something broke');
    expect($r->previous())->toBe($e);
});

it('constructs a branch result for condition / switch ops', function () {
    $r = OperationResult::withBranch('failure', ['reason' => 'too small']);
    expect($r->isSuccess())->toBeTrue();
    expect($r->branch())->toBe('failure');
    expect($r->output())->toBe(['reason' => 'too small']);
});

it('is immutable / readonly', function () {
    $rc = new ReflectionClass(OperationResult::class);
    foreach ($rc->getProperties() as $prop) {
        expect($prop->isReadOnly())->toBeTrue();
    }
});
