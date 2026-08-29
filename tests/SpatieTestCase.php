<?php

namespace Flexpik\FilamentStudio\Tests;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionServiceProvider;

/**
 * TestCase variant that also loads spatie/laravel-permission and its migrations.
 * Use this for tests that require the Spatie Permission models to exist.
 */
abstract class SpatieTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return array_merge(parent::getPackageProviders($app), [
            PermissionServiceProvider::class,
        ]);
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('auth.providers.users.model', SpatieUser::class);
        $app['config']->set('permission.models.permission', Permission::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $migrationStub = __DIR__.'/../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub';
        $tempDir = sys_get_temp_dir().'/filament_studio_test_migrations_'.getmypid();
        @mkdir($tempDir, 0755, true);
        copy($migrationStub, $tempDir.'/9999_01_01_000000_create_permission_tables.php');
        $this->loadMigrationsFrom($tempDir);

        $this->artisan('migrate', ['--database' => 'testing']);
    }

    /**
     * Create a user with the given permissions via Spatie.
     *
     * @param  array<string>  $permissions
     */
    protected function makeUserWith(array $permissions): SpatieUser
    {
        $user = SpatieUser::forceCreate([
            'name' => 'Test User',
            'email' => fake()->unique()->safeEmail(),
            'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        if (! empty($permissions)) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }
}
