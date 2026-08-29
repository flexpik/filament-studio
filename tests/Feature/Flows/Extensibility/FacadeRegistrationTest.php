<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Facades\FilamentStudio;
use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;
use Flexpik\FilamentStudio\Flows\Triggers\TriggerRegistry;
use Flexpik\FilamentStudio\Tests\Fixtures\Flows\FakeSlackOperation;
use Flexpik\FilamentStudio\Tests\Fixtures\Flows\FakeSlackOperationConfig;
use Flexpik\FilamentStudio\Tests\Fixtures\Flows\FakeStripeTrigger;
use Flexpik\FilamentStudio\Tests\Fixtures\Flows\FakeStripeTriggerConfig;

it('registers a flow operation through the facade', function () {
    FilamentStudio::registerFlowOperation(
        key: 'send_slack_message',
        label: 'Send Slack Message',
        icon: 'heroicon-o-chat-bubble-left',
        group: 'communication',
        activity: FakeSlackOperation::class,
        configSchema: FakeSlackOperationConfig::class,
        dangerous: false,
    );

    $registry = app(OperationRegistry::class);
    expect($registry->has('send_slack_message'))->toBeTrue();
    expect($registry->labelFor('send_slack_message'))->toBe('Send Slack Message');
    expect($registry->resolve('send_slack_message'))->toBeInstanceOf(FakeSlackOperation::class);
    expect($registry->configFor('send_slack_message'))->toBe(FakeSlackOperationConfig::class);
    expect($registry->isDangerous('send_slack_message'))->toBeFalse();
    expect($registry->groupFor('send_slack_message'))->toBe('communication');
});

it('registers a flow trigger through the facade', function () {
    FilamentStudio::registerFlowTrigger(
        key: 'stripe_webhook',
        label: 'Stripe Webhook',
        icon: 'heroicon-o-credit-card',
        handler: FakeStripeTrigger::class,
        configSchema: FakeStripeTriggerConfig::class,
    );

    $registry = app(TriggerRegistry::class);
    expect($registry->has('stripe_webhook'))->toBeTrue();
    expect($registry->resolve('stripe_webhook'))->toBeInstanceOf(FakeStripeTrigger::class);
});
