<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Operations\Logic\LogMessageActivity;
use Illuminate\Support\Facades\Log;

it('writes to the configured channel and returns logged=true', function () {
    Log::shouldReceive('channel')->with('daily')->andReturnSelf()
        ->shouldReceive('info')->once()->withArgs(fn ($msg) => $msg === 'hello');

    $ctx = makeOperationContext(config: ['level' => 'info', 'message' => 'hello']);
    $result = (new LogMessageActivity)->execute($ctx);

    expect($result->output())->toBe(['logged' => true, 'level' => 'info', 'message' => 'hello']);
});
