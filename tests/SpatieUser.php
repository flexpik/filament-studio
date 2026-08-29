<?php

namespace Flexpik\FilamentStudio\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * A test-only user model that includes Spatie's HasRoles trait.
 * Used by SpatieTestCase::makeUserWith() to create users with permissions.
 */
class SpatieUser extends Authenticatable
{
    use HasRoles;

    protected $table = 'users';

    protected $guarded = [];
}
