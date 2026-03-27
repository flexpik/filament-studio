<?php

namespace Flexpik\FilamentStudio\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Panel;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Flexpik\FilamentStudio\FilamentStudioPlugin;
use Flexpik\FilamentStudio\FilamentStudioServiceProvider;
use Illuminate\Foundation\Auth\User;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            SupportServiceProvider::class,
            SchemasServiceProvider::class,
            FormsServiceProvider::class,
            TablesServiceProvider::class,
            ActionsServiceProvider::class,
            NotificationsServiceProvider::class,
            InfolistsServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentServiceProvider::class,
            LivewireServiceProvider::class,
            FilamentStudioServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('filament-studio.table_prefix', 'studio_');
        $app['config']->set('auth.providers.users.model', User::class);

        Filament::registerPanel(fn (): Panel => $this->panel());
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../vendor/orchestra/testbench-core/laravel/migrations');

        $this->loadPackageMigrationStubs();

        $this->artisan('migrate', ['--database' => 'testing']);
    }

    /**
     * Copy .stub migrations to a temp directory as .php files so they can be loaded.
     */
    protected function loadPackageMigrationStubs(): void
    {
        $stubDir = __DIR__.'/../database/migrations';
        $tempDir = sys_get_temp_dir().'/filament-studio-migrations-'.md5($stubDir);

        // Clean up and recreate to avoid stale files
        if (is_dir($tempDir)) {
            array_map('unlink', glob("{$tempDir}/*.php"));
        } else {
            mkdir($tempDir, 0755, true);
        }

        $timestamp = 1;
        foreach (glob("{$stubDir}/*.php.stub") as $stub) {
            $filename = str_pad((string) $timestamp, 4, '0', STR_PAD_LEFT).'_01_01_000000_'.basename($stub, '.stub');
            copy($stub, "{$tempDir}/{$filename}");
            $timestamp++;
        }

        $this->loadMigrationsFrom($tempDir);
    }

    protected function panel(): Panel
    {
        return Panel::make()
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->plugin(FilamentStudioPlugin::make());
    }

    protected function authenticateUser(): User
    {
        $user = User::forceCreate([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        return $user;
    }
}
