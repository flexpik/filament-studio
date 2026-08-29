<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;

it('registers all 10 MVP operations on boot', function () {
    $registry = app(OperationRegistry::class);
    $expected = [
        'condition', 'transform_payload',
        'create_record', 'read_record', 'update_record', 'delete_record',
        'send_email', 'http_request',
        'log_message', 'trigger_flow',
    ];

    foreach ($expected as $key) {
        expect($registry->has($key))->toBeTrue("operation {$key} not registered");
    }
});
