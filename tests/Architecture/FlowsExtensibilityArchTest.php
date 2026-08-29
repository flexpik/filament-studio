<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Contracts\Flows\FlowOperation;
use Flexpik\FilamentStudio\Contracts\Flows\FlowTrigger;

arch('every built-in operation activity implements FlowOperation')
    ->expect('Flexpik\FilamentStudio\Flows\Operations')
    ->classes()
    ->toImplement(FlowOperation::class)
    ->ignoring([
        'Flexpik\FilamentStudio\Flows\Operations\OperationRegistry',
        'Flexpik\FilamentStudio\Flows\Operations\Validation\ConfigSchemaValidator',
        // *Config classes — they implement FlowOperationConfig, not FlowOperation
        'Flexpik\FilamentStudio\Flows\Operations\Logic\ConditionConfig',
        'Flexpik\FilamentStudio\Flows\Operations\Logic\LogMessageConfig',
        'Flexpik\FilamentStudio\Flows\Operations\Data\TransformPayloadConfig',
        'Flexpik\FilamentStudio\Flows\Operations\Composition\TriggerFlowConfig',
        'Flexpik\FilamentStudio\Flows\Operations\Communication\SendEmailConfig',
        'Flexpik\FilamentStudio\Flows\Operations\Communication\HttpRequestConfig',
        'Flexpik\FilamentStudio\Flows\Operations\Records\CreateRecordConfig',
        'Flexpik\FilamentStudio\Flows\Operations\Records\ReadRecordConfig',
        'Flexpik\FilamentStudio\Flows\Operations\Records\UpdateRecordConfig',
        'Flexpik\FilamentStudio\Flows\Operations\Records\DeleteRecordConfig',
    ]);

arch('every built-in trigger implements FlowTrigger')
    ->expect('Flexpik\FilamentStudio\Flows\Triggers')
    ->classes()
    ->toImplement(FlowTrigger::class)
    ->ignoring([
        'Flexpik\FilamentStudio\Flows\Triggers\TriggerRegistry',
        'Flexpik\FilamentStudio\Flows\Triggers\EventSubscriptionRegistry',
        // Schedule support classes
        'Flexpik\FilamentStudio\Flows\Triggers\Schedule\CronEvaluator',
        // *Config classes — they implement FlowTriggerConfig, not FlowTrigger
        'Flexpik\FilamentStudio\Flows\Triggers\ManualTriggerConfig',
        'Flexpik\FilamentStudio\Flows\Triggers\WebhookTriggerConfig',
        'Flexpik\FilamentStudio\Flows\Triggers\CollectionEventTriggerConfig',
        'Flexpik\FilamentStudio\Flows\Triggers\Schedule\ScheduleTriggerConfig',
    ]);

arch('Contracts\\Flows public classes do not depend on internal Operations or Triggers namespaces')
    ->expect('Flexpik\FilamentStudio\Contracts\Flows')
    ->not->toUse('Flexpik\FilamentStudio\Flows\Operations')
    ->not->toUse('Flexpik\FilamentStudio\Flows\Triggers');
