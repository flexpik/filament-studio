<?php

use Flexpik\FilamentStudio\Enums\StudioPermission;

it('has exactly 10 permission cases', function () {
    expect(StudioPermission::cases())->toHaveCount(10);
});

it('has correct string values for all cases', function () {
    expect(StudioPermission::ManageFields->value)->toBe('studio.manageFields')
        ->and(StudioPermission::ManageApiKeys->value)->toBe('studio.manageApiKeys')
        ->and(StudioPermission::ViewFlows->value)->toBe('view_flows')
        ->and(StudioPermission::CreateFlows->value)->toBe('create_flows')
        ->and(StudioPermission::UpdateFlows->value)->toBe('update_flows')
        ->and(StudioPermission::DeleteFlows->value)->toBe('delete_flows')
        ->and(StudioPermission::RunFlows->value)->toBe('run_flows');
});

it('returns all permission values as flat array', function () {
    $all = StudioPermission::values();

    expect($all)->toBeArray()
        ->toHaveCount(10)
        ->toContain('studio.manageFields', 'studio.manageApiKeys', 'view_flows', 'create_flows', 'update_flows', 'delete_flows', 'run_flows', 'publish_flow', 'run_dangerous_operations', 'export_flows');
});
