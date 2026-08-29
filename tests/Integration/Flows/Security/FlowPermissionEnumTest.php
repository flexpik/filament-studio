<?php

declare(strict_types=1);

use Flexpik\FilamentStudio\Enums\StudioPermission;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

it('exposes publish_flow and run_dangerous_operations cases', function () {
    expect(StudioPermission::values())
        ->toContain('publish_flow')
        ->toContain('run_dangerous_operations');
});

it('grants publish_flow to studio-editor and studio-admin, dangerous to admin only', function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    foreach (StudioPermission::values() as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName]);
    }

    $admin = Role::firstOrCreate(['name' => 'studio-admin']);
    $admin->syncPermissions(StudioPermission::values());

    $editor = Role::firstOrCreate(['name' => 'studio-editor']);
    $editor->syncPermissions([
        'view_flows',
        'create_flows',
        'update_flows',
        'delete_flows',
        'run_flows',
        'publish_flow',
    ]);

    expect($admin->hasPermissionTo('publish_flow'))->toBeTrue();
    expect($admin->hasPermissionTo('run_dangerous_operations'))->toBeTrue();
    expect($editor->hasPermissionTo('publish_flow'))->toBeTrue();
    expect($editor->hasPermissionTo('run_dangerous_operations'))->toBeFalse();
});
