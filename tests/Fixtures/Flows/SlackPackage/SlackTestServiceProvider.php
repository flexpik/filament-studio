<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Tests\Fixtures\Flows\SlackPackage;

use Flexpik\FilamentStudio\Facades\FilamentStudio;
use Illuminate\Support\ServiceProvider;

class SlackTestServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        FilamentStudio::registerFlowOperation(
            key: 'send_slack_message',
            label: 'Send Slack Message',
            activity: SendSlackOperation::class,
            configSchema: SendSlackOperationConfig::class,
            icon: 'heroicon-o-chat-bubble-left',
            group: 'communication',
            dangerous: false,
        );
    }
}
