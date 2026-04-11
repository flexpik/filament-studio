<?php

use Flexpik\FilamentStudio\Enums\StudioPermission;

it('returns collection permission actions', function () {
    $actions = StudioPermission::collectionActions();

    expect($actions)->toBe([
        'viewRecords',
        'createRecord',
        'updateRecord',
        'deleteRecord',
    ]);
});

it('generates permission names for a collection slug', function () {
    $permissions = StudioPermission::forCollection('products');

    expect($permissions)->toBe([
        'studio.collection.products.viewRecords',
        'studio.collection.products.createRecord',
        'studio.collection.products.updateRecord',
        'studio.collection.products.deleteRecord',
    ]);
});

it('generates permission labels for a collection slug', function () {
    $labels = StudioPermission::collectionPermissionLabels('products');

    expect($labels)->toBe([
        'studio.collection.products.viewRecords' => 'View Records',
        'studio.collection.products.createRecord' => 'Create Record',
        'studio.collection.products.updateRecord' => 'Update Record',
        'studio.collection.products.deleteRecord' => 'Delete Record',
    ]);
});
