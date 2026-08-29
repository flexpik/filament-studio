<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Jobs;

use Flexpik\FilamentStudio\Flows\Engine\FlowWorkflow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteFlowJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public string $flowRunId) {}

    public function handle(FlowWorkflow $workflow): void
    {
        $workflow->run($this->flowRunId);
    }

    public function uniqueId(): string
    {
        return $this->flowRunId;
    }

    public function uniqueFor(): int
    {
        return 60 * 30;
    }
}
