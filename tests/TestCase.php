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
use Laravel\Mcp\Server\McpServiceProvider;
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
            McpServiceProvider::class,
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

        // Migration stubs are now loaded by FilamentStudioServiceProvider::loadStubMigrations()
        // during packageBooted(), so we don't need to manually load them here.

        $this->artisan('migrate', ['--database' => 'testing']);
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
