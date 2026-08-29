<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Facades\FilamentStudio;
use Flexpik\FilamentStudio\Flows\Exceptions\DuplicateFlowOperationException;
use Flexpik\FilamentStudio\Flows\Exceptions\DuplicateFlowTriggerException;
use Flexpik\FilamentStudio\Tests\Fixtures\Flows\FakeSlackOperation;
use Flexpik\FilamentStudio\Tests\Fixtures\Flows\FakeStripeTrigger;

it('throws DuplicateFlowOperationException when the same op key is registered twice via facade', function () {
    FilamentStudio::registerFlowOperation(
        key: 'twice',
        label: 'Twice',
        activity: FakeSlackOperation::class,
    );
    FilamentStudio::registerFlowOperation(
        key: 'twice',
        label: 'Again',
        activity: FakeSlackOperation::class,
    );
})->throws(DuplicateFlowOperationException::class);

it('throws DuplicateFlowTriggerException when the same trigger key is registered twice via facade', function () {
    FilamentStudio::registerFlowTrigger(
        key: 'twice',
        label: 'Twice',
        handler: FakeStripeTrigger::class,
    );
    FilamentStudio::registerFlowTrigger(
        key: 'twice',
        label: 'Again',
        handler: FakeStripeTrigger::class,
    );
})->throws(DuplicateFlowTriggerException::class);

it('built-in registrations also throw on collision', function () {
    FilamentStudio::registerFlowOperation(
        key: 'condition',
        label: 'Hijacked',
        activity: FakeSlackOperation::class,
    );
})->throws(DuplicateFlowOperationException::class);
