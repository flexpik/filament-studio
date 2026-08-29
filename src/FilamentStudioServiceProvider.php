<?php

namespace Flexpik\FilamentStudio;

use Dedoc\Scramble\Scramble;
use Filament\Facades\Filament;
use Flexpik\FilamentStudio\Api\Flows\StudioFlowsApiRouteRegistrar;
use Flexpik\FilamentStudio\Api\OpenApi\StudioDocumentTransformer;
use Flexpik\FilamentStudio\Api\OpenApi\StudioOperationTransformer;
use Flexpik\FilamentStudio\Api\StudioApiRouteRegistrar;
use Flexpik\FilamentStudio\FieldTypes\FieldTypeRegistry;
use Flexpik\FilamentStudio\FieldTypes\Types;
use Flexpik\FilamentStudio\Flows\Commands\BackfillFlowVersioningCommand;
use Flexpik\FilamentStudio\Flows\Commands\DispatchScheduledFlowsCommand;
use Flexpik\FilamentStudio\Flows\Engine\GraphWalker;
use Flexpik\FilamentStudio\Flows\Engine\LogMaskingService;
use Flexpik\FilamentStudio\Flows\Engine\Templating\TemplateEngine;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Flows\Models\StudioFlowRun;
use Flexpik\FilamentStudio\Flows\Observers\RecordLifecycleObserver;
use Flexpik\FilamentStudio\Flows\Observers\StudioFlowObserver;
use Flexpik\FilamentStudio\Flows\Operations\Communication\HttpRequestActivity;
use Flexpik\FilamentStudio\Flows\Operations\Communication\HttpRequestConfig;
use Flexpik\FilamentStudio\Flows\Operations\Communication\SendEmailActivity;
use Flexpik\FilamentStudio\Flows\Operations\Communication\SendEmailConfig;
use Flexpik\FilamentStudio\Flows\Operations\Composition\TriggerFlowActivity;
use Flexpik\FilamentStudio\Flows\Operations\Composition\TriggerFlowConfig;
use Flexpik\FilamentStudio\Flows\Operations\Data\TransformPayloadActivity;
use Flexpik\FilamentStudio\Flows\Operations\Data\TransformPayloadConfig;
use Flexpik\FilamentStudio\Flows\Operations\Logic\ConditionActivity;
use Flexpik\FilamentStudio\Flows\Operations\Logic\ConditionConfig;
use Flexpik\FilamentStudio\Flows\Operations\Logic\LogMessageActivity;
use Flexpik\FilamentStudio\Flows\Operations\Logic\LogMessageConfig;
use Flexpik\FilamentStudio\Flows\Operations\NoOpActivity;
use Flexpik\FilamentStudio\Flows\Operations\OperationRegistry;
use Flexpik\FilamentStudio\Flows\Operations\Records\CreateRecordActivity;
use Flexpik\FilamentStudio\Flows\Operations\Records\CreateRecordConfig;
use Flexpik\FilamentStudio\Flows\Operations\Records\DeleteRecordActivity;
use Flexpik\FilamentStudio\Flows\Operations\Records\DeleteRecordConfig;
use Flexpik\FilamentStudio\Flows\Operations\Records\ReadRecordActivity;
use Flexpik\FilamentStudio\Flows\Operations\Records\ReadRecordConfig;
use Flexpik\FilamentStudio\Flows\Operations\Records\UpdateRecordActivity;
use Flexpik\FilamentStudio\Flows\Operations\Records\UpdateRecordConfig;
use Flexpik\FilamentStudio\Flows\Policies\FlowPolicy;
use Flexpik\FilamentStudio\Flows\Security\MasksSensitiveValues;
use Flexpik\FilamentStudio\Flows\Triggers\CollectionEventTrigger;
use Flexpik\FilamentStudio\Flows\Triggers\EventSubscriptionRegistry;
use Flexpik\FilamentStudio\Flows\Triggers\ManualTrigger;
use Flexpik\FilamentStudio\Flows\Triggers\Schedule\ScheduleTrigger;
use Flexpik\FilamentStudio\Flows\Triggers\TriggerRegistry;
use Flexpik\FilamentStudio\Flows\Triggers\WebhookTrigger;
use Flexpik\FilamentStudio\Mcp\StudioMcpServer;
use Flexpik\FilamentStudio\Mcp\Support\StudioApiKeyContext;
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
use Flexpik\FilamentStudio\Services\LocaleResolver;
use Flexpik\FilamentStudio\Services\VariableResolver;
use Flexpik\FilamentStudio\Support\FilamentStudioManager;
use Flexpik\FilamentStudio\Support\PermissionRegistrar;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Mcp\Facades\Mcp;
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
                'add_api_enabled_to_studio_collections_table',
                'z_add_multilingual_columns',
                'create_studio_flows_table',
                'create_studio_flow_versions_table',
                'create_studio_flow_runs_table',
                'create_studio_flow_run_steps_table',
                'create_studio_flow_secrets_table',
                'z_add_versioning_columns_to_flows',
                'z_migrate_mvp_flows_to_versioning',
                'z_alter_studio_flows_add_webhook_auth_mode',
                'create_studio_flow_audit_log_table',
                'z_add_observability_columns_to_flow_runs_and_steps',
            ])
            ->hasViews();
    }

    public function packageRegistered(): void
    {
        $this->app->scoped(StudioApiKeyContext::class);

        $this->app->bind(EavQueryBuilder::class, function ($app, $params) {
            return new EavQueryBuilder($params['collection']);
        });

        $this->app->singleton(VariableResolver::class);
        $this->app->singleton(LocaleResolver::class);
        $this->app->singleton(FilamentStudioManager::class);

        $this->app->singleton(OperationRegistry::class, function () {
            $registry = new OperationRegistry;
            $registry->register('noop', 'No-op', NoOpActivity::class);

            $registry->register('condition', 'Condition', ConditionActivity::class, configSchema: ConditionConfig::class);
            $registry->register('transform_payload', 'Transform Payload', TransformPayloadActivity::class, configSchema: TransformPayloadConfig::class);

            $registry->register('create_record', 'Create Record', CreateRecordActivity::class, configSchema: CreateRecordConfig::class);
            $registry->register('read_record', 'Read Record', ReadRecordActivity::class, configSchema: ReadRecordConfig::class);
            $registry->register('update_record', 'Update Record', UpdateRecordActivity::class, configSchema: UpdateRecordConfig::class, dangerous: true);
            $registry->register('delete_record', 'Delete Record', DeleteRecordActivity::class, configSchema: DeleteRecordConfig::class, dangerous: true);

            $registry->register('send_email', 'Send Email', SendEmailActivity::class, configSchema: SendEmailConfig::class);
            $registry->register('http_request', 'HTTP Request', HttpRequestActivity::class, configSchema: HttpRequestConfig::class, dangerous: true);

            $registry->register('log_message', 'Log Message', LogMessageActivity::class, configSchema: LogMessageConfig::class);
            $registry->register('trigger_flow', 'Trigger Flow', TriggerFlowActivity::class, configSchema: TriggerFlowConfig::class);

            return $registry;
        });

        $this->app->singleton(TriggerRegistry::class);
        $this->app->singleton(EventSubscriptionRegistry::class);
        $this->app->singleton(TemplateEngine::class);
        $this->app->singleton(GraphWalker::class);
        $this->app->singleton(LogMaskingService::class);
        $this->app->singleton(MasksSensitiveValues::class);

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
        $this->loadStubMigrations();

        \Livewire\Livewire::component('filter-builder', Livewire\FilterBuilder::class);
        \Livewire\Livewire::component('studio-locale-switcher', Livewire\LocaleSwitcher::class);

        StudioRecord::observe(RecordVersioningObserver::class);
        StudioRecord::observe(RecordLifecycleObserver::class);
        StudioCollection::observe(StudioCollectionObserver::class);
        StudioFlow::observe(StudioFlowObserver::class);

        $this->bootDefaultFlowTriggers();
        $this->bootFlowScheduler();
        $this->commands([
            BackfillFlowVersioningCommand::class,
            DispatchScheduledFlowsCommand::class,
        ]);

        Gate::policy(StudioCollection::class, StudioCollectionPolicy::class);
        Gate::policy(StudioDashboard::class, StudioDashboardPolicy::class);
        Gate::policy(StudioApiKey::class, StudioApiKeyPolicy::class);
        Gate::policy(StudioFlow::class, FlowPolicy::class);

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

        RateLimiter::for('studio-mcp', function ($request) {
            $limit = config('filament-studio.mcp.http.rate_limit', 120);
            $key = $request->header('X-Api-Key', $request->ip());

            return Limit::perMinute($limit)->by($key);
        });

        RateLimiter::for('studio-flow-webhook', function (Request $request) {
            $limit = config('filament-studio.flows.webhook_rate_limit_per_minute', 60);
            $key = ($request->route('flowSlug') ?? '').'|'.$request->ip();

            return Limit::perMinute($limit)
                ->by($key)
                ->response(fn () => response()->json(['error' => 'rate_limited'], 429)
                    ->header('Retry-After', '60'));
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

            if (config('filament-studio.flows.enabled', false)) {
                StudioFlowsApiRouteRegistrar::register();
            }
        });

        $this->app->booted(function () {
            if (! config('filament-studio.mcp.enabled', false)) {
                return;
            }

            if (config('filament-studio.mcp.http.enabled', true)) {
                require __DIR__.'/Mcp/Routes.php';
            }

            if (config('filament-studio.mcp.stdio.enabled', true)) {
                $handle = config('filament-studio.mcp.stdio.handle', 'studio');
                Mcp::local($handle, StudioMcpServer::class);
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

    protected function bootDefaultFlowTriggers(): void
    {
        $registry = $this->app->make(TriggerRegistry::class);

        $registry->register('manual', 'Manual', ManualTrigger::class);
        $registry->register('webhook', 'Webhook', WebhookTrigger::class);
        $registry->register('collection_event', 'Collection Event', CollectionEventTrigger::class);
        $registry->register('schedule', 'Schedule', ScheduleTrigger::class);
    }

    protected function bootFlowScheduler(): void
    {
        $this->app->afterResolving(Schedule::class, function ($schedule) {
            if (! config('filament-studio.flows.enabled', false)) {
                return;
            }

            $schedule->command('studio:flows:dispatch-scheduled')
                ->everyMinute()
                ->withoutOverlapping()
                ->onOneServer();

            $schedule->command('model:prune', ['--model' => [StudioFlowRun::class]])
                ->daily();
        });
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

    protected function loadStubMigrations(): void
    {
        $stubDir = __DIR__.'/../database/migrations';
        $cacheKey = md5(implode(',', $this->package->migrationFileNames));
        $tempDir = sys_get_temp_dir().'/filament-studio-migrations-'.$cacheKey;

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);

            // Use ordered list from hasMigrations() so FK dependencies are respected.
            // Prefix with 2020_ so they sort after Laravel's 0001_ default migrations.
            foreach (array_values($this->package->migrationFileNames) as $i => $name) {
                $seq = str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT);
                $stub = "{$stubDir}/{$name}.php.stub";
                if (file_exists($stub)) {
                    copy($stub, "{$tempDir}/2020_01_01_{$seq}_{$name}.php");
                }
            }
        }

        $this->loadMigrationsFrom($tempDir);
    }
}
