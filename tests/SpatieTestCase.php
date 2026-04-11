<?php

namespace Flexpik\FilamentStudio\Tests;

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

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $migrationStub = __DIR__.'/../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub';
        $tempFile = sys_get_temp_dir().'/9999_01_01_000000_create_permission_tables.php';

        copy($migrationStub, $tempFile);
        $this->loadMigrationsFrom(dirname($tempFile));

        $this->artisan('migrate', ['--database' => 'testing']);
    }
}
