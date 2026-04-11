<?php

namespace Flexpik\FilamentStudio;

use Dedoc\Scramble\Scramble;
use Filament\Facades\Filament;
use Flexpik\FilamentStudio\Api\OpenApi\StudioDocumentTransformer;
use Flexpik\FilamentStudio\Api\OpenApi\StudioOperationTransformer;
use Flexpik\FilamentStudio\Api\StudioApiRouteRegistrar;
use Flexpik\FilamentStudio\FieldTypes\FieldTypeRegistry;
use Flexpik\FilamentStudio\FieldTypes\Types;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioRecord;
use Flexpik\FilamentStudio\Observers\RecordVersioningObserver;
use Flexpik\FilamentStudio\Observers\StudioCollectionObserver;
use Flexpik\FilamentStudio\Panels\PanelTypeRegistry;
use Flexpik\FilamentStudio\Panels\Types\BarChartPanel;
use Flexpik\FilamentStudio\Panels\Types\LabelPanel;
use Flexpik\FilamentStudio\Panels\Types\LineChartPanel;
use Flexpik\FilamentStudio\Panels\Types\ListPanel;
use Flexpik\FilamentStudio\Panels\Types\MeterPanel;
use Flexpik\FilamentStudio\Panels\Types\MetricPanel;
use Flexpik\FilamentStudio\Panels\Types\PieChartPanel;
use Flexpik\FilamentStudio\Panels\Types\TimeSeriesPanel;
use Flexpik\FilamentStudio\Panels\Types\VariablePanel;
use Flexpik\FilamentStudio\Policies\StudioApiKeyPolicy;
use Flexpik\FilamentStudio\Policies\StudioCollectionPolicy;
use Flexpik\FilamentStudio\Policies\StudioDashboardPolicy;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;
use Flexpik\FilamentStudio\Services\VariableResolver;
use Flexpik\FilamentStudio\Support\PermissionRegistrar;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentStudioServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-studio';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasMigrations([
                'create_studio_collections_table',
                'create_studio_fields_table',
                'create_studio_records_table',
                'create_studio_values_table',
                'create_studio_field_options_table',
                'create_studio_migration_logs_table',
                'create_studio_record_versions_table',
                'create_studio_saved_filters_table',
                'create_studio_dashboards_table',
                'create_studio_panels_table',
                'create_studio_api_keys_table',
            ])
            ->hasViews();
    }

    public function packageRegistered(): void
    {
        $this->app->bind(EavQueryBuilder::class, function ($app, $params) {
            return new EavQueryBuilder($params['collection']);
        });

        $this->app->singleton(VariableResolver::class);

        $this->app->singleton(PanelTypeRegistry::class, function () {
            $registry = new PanelTypeRegistry;

            $registry->register(MetricPanel::class);
            $registry->register(ListPanel::class);
            $registry->register(TimeSeriesPanel::class);
            $registry->register(BarChartPanel::class);
            $registry->register(LineChartPanel::class);
            $registry->register(MeterPanel::class);
            $registry->register(PieChartPanel::class);
            $registry->register(LabelPanel::class);
            $registry->register(VariablePanel::class);

            return $registry;
        });

        $this->app->singleton(FieldTypeRegistry::class, function () {
            $registry = new FieldTypeRegistry;

            // Register all 33 built-in field types
            $types = [
                Types\TextFieldType::class,
                Types\TextareaFieldType::class,
                Types\RichEditorFieldType::class,
                Types\MarkdownFieldType::class,
                Types\PasswordFieldType::class,
                Types\IntegerFieldType::class,
                Types\DecimalFieldType::class,
                Types\RangeFieldType::class,
                Types\CheckboxFieldType::class,
                Types\ToggleFieldType::class,
                Types\SlugFieldType::class,
                Types\SelectFieldType::class,
                Types\MultiSelectFieldType::class,
                Types\RadioFieldType::class,
                Types\CheckboxListFieldType::class,
                Types\DateFieldType::class,
                Types\TimeFieldType::class,
                Types\DatetimeFieldType::class,
                Types\FileFieldType::class,
                Types\ImageFieldType::class,
                Types\AvatarFieldType::class,
                Types\BelongsToFieldType::class,
                Types\HasManyFieldType::class,
                Types\BelongsToManyFieldType::class,
                Types\RepeaterFieldType::class,
                Types\BuilderFieldType::class,
                Types\KeyValueFieldType::class,
                Types\TagsFieldType::class,
                Types\ColorFieldType::class,
                Types\DividerFieldType::class,
                Types\HiddenFieldType::class,
                Types\SectionHeaderFieldType::class,
                Types\CalloutFieldType::class,
            ];

            foreach ($types as $type) {
                $registry->register($type);
            }

            return $registry;
        });
    }

    public function packageBooted(): void
    {
        \Livewire\Livewire::component('filter-builder', Livewire\FilterBuilder::class);

        StudioRecord::observe(RecordVersioningObserver::class);
        StudioCollection::observe(StudioCollectionObserver::class);

        Gate::policy(StudioCollection::class, StudioCollectionPolicy::class);
        Gate::policy(StudioDashboard::class, StudioDashboardPolicy::class);
        Gate::policy(StudioApiKey::class, StudioApiKeyPolicy::class);

        if (class_exists(ActivitylogServiceProvider::class)) {
            $this->registerActivityLogging();
        }

        if (config('filament-studio.permissions.auto_register', true)) {
            $this->app->booted(function () {
                PermissionRegistrar::sync(config('filament-studio.permissions.guard'));
            });
        }

        RateLimiter::for('studio-api', function ($request) {
            $limit = config('filament-studio.api.rate_limit', 60);
            $key = $request->header('X-Api-Key', $request->ip());

            return Limit::perMinute($limit)->by($key);
        });

        $this->app->booted(function () {
            // Check both config and plugin instance — the plugin sets config
            // during Filament panel boot, which may run after app booted callbacks.
            $apiEnabled = config('filament-studio.api.enabled', false)
                || $this->pluginHasApiEnabled();

            if ($apiEnabled) {
                StudioApiRouteRegistrar::register();

                if (class_exists(Scramble::class)) {
                    Scramble::configure()
                        ->withDocumentTransformers([
                            new StudioDocumentTransformer,
                        ])
                        ->withOperationTransformers([
                            new StudioOperationTransformer,
                        ]);
                }
            }
        });
    }

    protected function pluginHasApiEnabled(): bool
    {
        try {
            foreach (Filament::getPanels() as $panel) {
                if (! $panel->hasPlugin('filament-studio')) {
                    continue;
                }

                $plugin = $panel->getPlugin('filament-studio');

                if ($plugin instanceof FilamentStudioPlugin && $plugin->isApiEnabled()) {
                    return true;
                }
            }
        } catch (\Throwable) {
            // Filament not yet booted or panels not available
        }

        return false;
    }

    protected function registerActivityLogging(): void
    {
        if (! function_exists('activity')) {
            return;
        }

        StudioRecord::created(function (StudioRecord $record) {
            activity('studio')
                ->performedOn($record)
                ->causedBy(auth()->user())
                ->log('created');
        });

        StudioRecord::updated(function (StudioRecord $record) {
            activity('studio')
                ->performedOn($record)
                ->causedBy(auth()->user())
                ->log('updated');
        });

        StudioRecord::deleted(function (StudioRecord $record) {
            activity('studio')
                ->performedOn($record)
                ->causedBy(auth()->user())
                ->log('deleted');
        });
    }
}
