<?php

use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Policies\StudioCollectionPolicy;
use Illuminate\Foundation\Auth\User;

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
    it('grants access when user has permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.viewAny', true);

        expect($policy->viewAny($user))->toBeTrue();
    });

    it('denies access when user lacks permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.viewAny', false);

        expect($policy->viewAny($user))->toBeFalse();
    });

    it('defaults to true when user has no hasPermissionTo method', function () {
        $policy = new StudioCollectionPolicy;
        $user = new User;

        expect($policy->viewAny($user))->toBeTrue();
    });
});

describe('create', function () {
    it('grants access when user has permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.create', true);

        expect($policy->create($user))->toBeTrue();
    });

    it('denies access when user lacks permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.create', false);

        expect($policy->create($user))->toBeFalse();
    });

    it('defaults to true when user has no hasPermissionTo method', function () {
        $policy = new StudioCollectionPolicy;
        $user = new User;

        expect($policy->create($user))->toBeTrue();
    });
});

describe('update', function () {
    it('grants access when user has permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.update', true);
        $collection = StudioCollection::factory()->make();

        expect($policy->update($user, $collection))->toBeTrue();
    });

    it('denies access when user lacks permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.update', false);
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
    it('grants access when user has permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.delete', true);
        $collection = StudioCollection::factory()->make();

        expect($policy->delete($user, $collection))->toBeTrue();
    });

    it('denies access when user lacks permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.delete', false);
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
    it('grants access when user has permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.viewRecords', true);
        $collection = StudioCollection::factory()->make();

        expect($policy->viewRecords($user, $collection))->toBeTrue();
    });

    it('denies access when user lacks permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.viewRecords', false);
        $collection = StudioCollection::factory()->make();

        expect($policy->viewRecords($user, $collection))->toBeFalse();
    });

    it('defaults to true when user has no hasPermissionTo method', function () {
        $policy = new StudioCollectionPolicy;
        $user = new User;
        $collection = StudioCollection::factory()->make();

        expect($policy->viewRecords($user, $collection))->toBeTrue();
    });
});

describe('createRecord', function () {
    it('grants access when user has permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.createRecord', true);
        $collection = StudioCollection::factory()->make();

        expect($policy->createRecord($user, $collection))->toBeTrue();
    });

    it('denies access when user lacks permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.createRecord', false);
        $collection = StudioCollection::factory()->make();

        expect($policy->createRecord($user, $collection))->toBeFalse();
    });

    it('defaults to true when user has no hasPermissionTo method', function () {
        $policy = new StudioCollectionPolicy;
        $user = new User;
        $collection = StudioCollection::factory()->make();

        expect($policy->createRecord($user, $collection))->toBeTrue();
    });
});

describe('updateRecord', function () {
    it('grants access when user has permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.updateRecord', true);
        $collection = StudioCollection::factory()->make();

        expect($policy->updateRecord($user, $collection))->toBeTrue();
    });

    it('denies access when user lacks permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.updateRecord', false);
        $collection = StudioCollection::factory()->make();

        expect($policy->updateRecord($user, $collection))->toBeFalse();
    });

    it('defaults to true when user has no hasPermissionTo method', function () {
        $policy = new StudioCollectionPolicy;
        $user = new User;
        $collection = StudioCollection::factory()->make();

        expect($policy->updateRecord($user, $collection))->toBeTrue();
    });
});

describe('deleteRecord', function () {
    it('grants access when user has permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.deleteRecord', true);
        $collection = StudioCollection::factory()->make();

        expect($policy->deleteRecord($user, $collection))->toBeTrue();
    });

    it('denies access when user lacks permission', function () {
        $policy = new StudioCollectionPolicy;
        $user = createUserWithPermission('studio.deleteRecord', false);
        $collection = StudioCollection::factory()->make();

        expect($policy->deleteRecord($user, $collection))->toBeFalse();
    });

    it('defaults to true when user has no hasPermissionTo method', function () {
        $policy = new StudioCollectionPolicy;
        $user = new User;
        $collection = StudioCollection::factory()->make();

        expect($policy->deleteRecord($user, $collection))->toBeTrue();
    });
});
