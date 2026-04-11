<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Policies\StudioCollectionPolicy;
use Illuminate\Foundation\Auth\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

function createUserWithPermission(string $permission, bool $granted): User
{
    $user = new class extends User
    {
        public string $expectedPermission = '';

        public bool $granted = false;

        public function hasPermissionTo(string $permission): bool
        {
            return $permission === $this->expectedPermission && $this->granted;
        }
    };

    $user->expectedPermission = $permission;
    $user->granted = $granted;

    return $user;
}

describe('viewAny', function () {
    it('grants access when user has Shield permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('ViewAny:StudioCollection', true);

        expect($policy->viewAny($user))->toBeTrue();
    });

    it('denies access when user lacks Shield permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('ViewAny:StudioCollection', false);

        expect($policy->viewAny($user))->toBeFalse();
    });

    it('defaults to true when user has no hasPermissionTo method', function () {
        $policy = new StudioCollectionPolicy;
        $user = new User;

        expect($policy->viewAny($user))->toBeTrue();
    });
});

describe('view', function () {
    it('grants access when user has Shield permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('View:StudioCollection', true);
        $collection = StudioCollection::factory()->make();

        expect($policy->view($user, $collection))->toBeTrue();
    });

    it('denies access when user lacks Shield permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('View:StudioCollection', false);
        $collection = StudioCollection::factory()->make();

        expect($policy->view($user, $collection))->toBeFalse();
    });
});

describe('create', function () {
    it('grants access when user has Shield permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('Create:StudioCollection', true);

        expect($policy->create($user))->toBeTrue();
    });

    it('denies access when user lacks Shield permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('Create:StudioCollection', false);

        expect($policy->create($user))->toBeFalse();
    });

    it('defaults to true when user has no hasPermissionTo method', function () {
        $policy = new StudioCollectionPolicy;
        $user = new User;

        expect($policy->create($user))->toBeTrue();
    });
});

describe('update', function () {
    it('grants access when user has Shield permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('Update:StudioCollection', true);
        $collection = StudioCollection::factory()->make();

        expect($policy->update($user, $collection))->toBeTrue();
    });

    it('denies access when user lacks Shield permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('Update:StudioCollection', false);
        $collection = StudioCollection::factory()->make();

        expect($policy->update($user, $collection))->toBeFalse();
    });

    it('defaults to true when user has no hasPermissionTo method', function () {
        $policy = new StudioCollectionPolicy;
        $user = new User;
        $collection = StudioCollection::factory()->make();

        expect($policy->update($user, $collection))->toBeTrue();
    });
});

describe('delete', function () {
    it('grants access when user has Shield permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('Delete:StudioCollection', true);
        $collection = StudioCollection::factory()->make();

        expect($policy->delete($user, $collection))->toBeTrue();
    });

    it('denies access when user lacks Shield permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('Delete:StudioCollection', false);
        $collection = StudioCollection::factory()->make();

        expect($policy->delete($user, $collection))->toBeFalse();
    });

    it('defaults to true when user has no hasPermissionTo method', function () {
        $policy = new StudioCollectionPolicy;
        $user = new User;
        $collection = StudioCollection::factory()->make();

        expect($policy->delete($user, $collection))->toBeTrue();
    });
});

describe('manageFields', function () {
    it('grants access when user has permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.manageFields', true);
        $collection = StudioCollection::factory()->make();

        expect($policy->manageFields($user, $collection))->toBeTrue();
    });

    it('denies access when user lacks permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.manageFields', false);
        $collection = StudioCollection::factory()->make();

        expect($policy->manageFields($user, $collection))->toBeFalse();
    });

    it('defaults to true when user has no hasPermissionTo method', function () {
        $policy = new StudioCollectionPolicy;
        $user = new User;
        $collection = StudioCollection::factory()->make();

        expect($policy->manageFields($user, $collection))->toBeTrue();
    });
});

describe('viewRecords', function () {
    it('grants access when user has per-collection permission', function () {
        $policy = new StudioCollectionPolicy;
        $collection = StudioCollection::factory()->make(['slug' => 'products']);
        $user = createUserWithPermission('studio.collection.products.viewRecords', true);

        expect($policy->viewRecords($user, $collection))->toBeTrue();
    });

    it('denies access when user lacks per-collection permission', function () {
        $policy = new StudioCollectionPolicy;
        $collection = StudioCollection::factory()->make(['slug' => 'products']);
        $user = createUserWithPermission('studio.collection.products.viewRecords', false);

        expect($policy->viewRecords($user, $collection))->toBeFalse();
    });

    it('denies access for a different collection slug', function () {
        $policy = new StudioCollectionPolicy;
        $collection = StudioCollection::factory()->make(['slug' => 'orders']);
        $user = createUserWithPermission('studio.collection.products.viewRecords', true);

        expect($policy->viewRecords($user, $collection))->toBeFalse();
    });

    it('defaults to true when user has no hasPermissionTo method', function () {
        $policy = new StudioCollectionPolicy;
        $user = new User;
        $collection = StudioCollection::factory()->make(['slug' => 'products']);

        expect($policy->viewRecords($user, $collection))->toBeTrue();
    });

    it('denies access when permission does not exist in database', function () {
        $policy = new StudioCollectionPolicy;
        $collection = StudioCollection::factory()->make(['slug' => 'products']);

        $user = new class extends User
        {
            public function hasPermissionTo(string $permission): bool
            {
                throw PermissionDoesNotExist::create($permission, 'web');
            }
        };

        expect($policy->viewRecords($user, $collection))->toBeFalse();
    });
});

describe('createRecord', function () {
    it('grants access when user has per-collection permission', function () {
        $policy = new StudioCollectionPolicy;
        $collection = StudioCollection::factory()->make(['slug' => 'products']);
        $user = createUserWithPermission('studio.collection.products.createRecord', true);

        expect($policy->createRecord($user, $collection))->toBeTrue();
    });

    it('denies access when user lacks per-collection permission', function () {
        $policy = new StudioCollectionPolicy;
        $collection = StudioCollection::factory()->make(['slug' => 'products']);
        $user = createUserWithPermission('studio.collection.products.createRecord', false);

        expect($policy->createRecord($user, $collection))->toBeFalse();
    });

    it('denies access for a different collection slug', function () {
        $policy = new StudioCollectionPolicy;
        $collection = StudioCollection::factory()->make(['slug' => 'orders']);
        $user = createUserWithPermission('studio.collection.products.createRecord', true);

        expect($policy->createRecord($user, $collection))->toBeFalse();
    });

    it('defaults to true when user has no hasPermissionTo method', function () {
        $policy = new StudioCollectionPolicy;
        $user = new User;
        $collection = StudioCollection::factory()->make(['slug' => 'products']);

        expect($policy->createRecord($user, $collection))->toBeTrue();
    });
});

describe('updateRecord', function () {
    it('grants access when user has per-collection permission', function () {
        $policy = new StudioCollectionPolicy;
        $collection = StudioCollection::factory()->make(['slug' => 'products']);
        $user = createUserWithPermission('studio.collection.products.updateRecord', true);

        expect($policy->updateRecord($user, $collection))->toBeTrue();
    });

    it('denies access when user lacks per-collection permission', function () {
        $policy = new StudioCollectionPolicy;
        $collection = StudioCollection::factory()->make(['slug' => 'products']);
        $user = createUserWithPermission('studio.collection.products.updateRecord', false);

        expect($policy->updateRecord($user, $collection))->toBeFalse();
    });

    it('denies access for a different collection slug', function () {
        $policy = new StudioCollectionPolicy;
        $collection = StudioCollection::factory()->make(['slug' => 'orders']);
        $user = createUserWithPermission('studio.collection.products.updateRecord', true);

        expect($policy->updateRecord($user, $collection))->toBeFalse();
    });

    it('defaults to true when user has no hasPermissionTo method', function () {
        $policy = new StudioCollectionPolicy;
        $user = new User;
        $collection = StudioCollection::factory()->make(['slug' => 'products']);

        expect($policy->updateRecord($user, $collection))->toBeTrue();
    });
});

describe('deleteRecord', function () {
    it('grants access when user has per-collection permission', function () {
        $policy = new StudioCollectionPolicy;
        $collection = StudioCollection::factory()->make(['slug' => 'products']);
        $user = createUserWithPermission('studio.collection.products.deleteRecord', true);

        expect($policy->deleteRecord($user, $collection))->toBeTrue();
    });

    it('denies access when user lacks per-collection permission', function () {
        $policy = new StudioCollectionPolicy;
        $collection = StudioCollection::factory()->make(['slug' => 'products']);
        $user = createUserWithPermission('studio.collection.products.deleteRecord', false);

        expect($policy->deleteRecord($user, $collection))->toBeFalse();
    });

    it('denies access for a different collection slug', function () {
        $policy = new StudioCollectionPolicy;
        $collection = StudioCollection::factory()->make(['slug' => 'orders']);
        $user = createUserWithPermission('studio.collection.products.deleteRecord', true);

        expect($policy->deleteRecord($user, $collection))->toBeFalse();
    });

    it('defaults to true when user has no hasPermissionTo method', function () {
        $policy = new StudioCollectionPolicy;
        $user = new User;
        $collection = StudioCollection::factory()->make(['slug' => 'products']);

        expect($policy->deleteRecord($user, $collection))->toBeTrue();
    });
});
