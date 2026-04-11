<?php

namespace Flexpik\FilamentStudio;

use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Pages\StudioDashboardPage;
use Flexpik\FilamentStudio\Panels\AbstractStudioPanel;
use Flexpik\FilamentStudio\Panels\PanelTypeRegistry;
use Flexpik\FilamentStudio\Resources\ApiSettingsResource;
use Flexpik\FilamentStudio\Resources\CollectionManagerResource;
use Flexpik\FilamentStudio\Resources\DashboardResource;
use Flexpik\FilamentStudio\Resources\DynamicCollectionResource;
use Flexpik\FilamentStudio\Services\ConditionEvaluator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class FilamentStudioPlugin implements Plugin
{
    protected string $navigationGroup = 'Studio';

    protected string $schemaNavigationLabel = 'Schema Manager';

    protected bool $versioningEnabled = false;

    protected bool $softDeletesEnabled = false;

    protected bool $scoutEnabled = false;

    protected bool $apiEnabled = false;

    /** @var array<string, class-string> */
    protected array $additionalFieldTypes = [];

    /** @var array<class-string<AbstractStudioPanel>> */
    protected array $additionalPanelTypes = [];

    protected ?\Closure $afterTenantCreatedCallback = null;

    // --- Static Hook Registry ---

    /** @var array<int, \Closure> */
    protected static array $afterTenantCreatedHooks = [];

    /** @var array<int, \Closure> */
    protected static array $afterCollectionCreatedHooks = [];

    /** @var array<int, \Closure> */
    protected static array $afterFieldAddedHooks = [];

    /** @var array<int, \Closure> */
    protected static array $modifyFormSchemaHooks = [];

    /** @var array<int, \Closure> */
    protected static array $modifyTableColumnsHooks = [];

    /** @var array<int, \Closure> */
    protected static array $modifyQueryHooks = [];

    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-studio';
    }

    public function register(Panel $panel): void
    {
        if ($this->apiEnabled) {
            $panel->resources([
                ApiSettingsResource::class,
            ]);
        }

        $panel->resources([
            CollectionManagerResource::class,
            DynamicCollectionResource::class,
            DashboardResource::class,
        ]);

        $panel->pages([
            StudioDashboardPage::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        if ($this->apiEnabled) {
            config()->set('filament-studio.api.enabled', true);
        }

        if (! empty($this->additionalPanelTypes)) {
            $registry = app(PanelTypeRegistry::class);
            foreach ($this->additionalPanelTypes as $type) {
                $registry->register($type);
            }
        }

        Filament::serving(function () {
            try {
                $items = array_merge(
                    $this->getCollectionNavigationItems(),
                    $this->getDashboardNavigationItems(),
                );
            } catch (QueryException) {
                return;
            }

            if (count($items) > 0) {
                Filament::registerNavigationItems($items);
            }
        });
    }

    // --- Navigation Items ---

    /**
     * Build NavigationItem entries for all visible, tenant-scoped collections.
     *
     * @return array<NavigationItem>
     */
    public function getCollectionNavigationItems(): array
    {
        $tenant = Filament::getTenant();
        $user = auth()->user();

        return StudioCollection::query()
            ->forTenant($tenant?->getKey())
            ->visible()
            ->orderBy('name')
            ->get()
            ->filter(fn (StudioCollection $collection) => $user?->can('viewRecords', $collection) ?? false)
            ->map(fn (StudioCollection $collection) => NavigationItem::make($collection->label_plural)
                ->group($this->navigationGroup)
                ->icon($collection->icon ?? 'heroicon-o-table-cells')
                ->url(DynamicCollectionResource::getUrl('index', [
                    'collection_slug' => $collection->slug,
                ]))
                ->isActiveWhen(fn () => request()->route('collection_slug') === $collection->slug)
                ->sort(10)
            )
            ->all();
    }

    /**
     * Build NavigationItem entries for all tenant-scoped dashboards.
     *
     * @return array<NavigationItem>
     */
    public function getDashboardNavigationItems(): array
    {
        if (! StudioDashboardPage::canAccess()) {
            return [];
        }

        $tenantId = Filament::getTenant()?->getKey();

        $dashboards = StudioDashboard::query()
            ->forTenant($tenantId)
            ->ordered()
            ->get();

        return $dashboards->map(fn (StudioDashboard $dashboard) => NavigationItem::make($dashboard->name)
            ->icon($dashboard->icon ?? 'heroicon-o-chart-pie')
            ->url(StudioDashboardPage::getUrl(['dashboardSlug' => $dashboard->slug]))
            ->group($this->navigationGroup)
            ->sort(100 + $dashboard->sort_order)
        )->toArray();
    }

    // --- Navigation Configuration ---

    public function navigationGroup(string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): string
    {
        return $this->navigationGroup;
    }

    public function schemaNavigationLabel(string $label): static
    {
        $this->schemaNavigationLabel = $label;

        return $this;
    }

    public function getSchemaNavigationLabel(): string
    {
        return $this->schemaNavigationLabel;
    }

    // --- Feature Flags ---

    public function enableVersioning(bool $enabled = true): static
    {
        $this->versioningEnabled = $enabled;

        return $this;
    }

    public function isVersioningEnabled(): bool
    {
        return $this->versioningEnabled;
    }

    public function enableSoftDeletes(bool $enabled = true): static
    {
        $this->softDeletesEnabled = $enabled;

        return $this;
    }

    public function isSoftDeletesEnabled(): bool
    {
        return $this->softDeletesEnabled;
    }

    public function useScout(bool $enabled = true): static
    {
        $this->scoutEnabled = $enabled;

        return $this;
    }

    public function isScoutEnabled(): bool
    {
        return $this->scoutEnabled;
    }

    public function enableApi(bool $enabled = true): static
    {
        $this->apiEnabled = $enabled;

        return $this;
    }

    public function isApiEnabled(): bool
    {
        return $this->apiEnabled;
    }

    // --- Additional Field Types ---

    /**
     * @param  array<string, class-string>  $types
     */
    public function fieldTypes(array $types): static
    {
        $this->additionalFieldTypes = $types;

        return $this;
    }

    /**
     * @return array<string, class-string>
     */
    public function getAdditionalFieldTypes(): array
    {
        return $this->additionalFieldTypes;
    }

    // --- Additional Panel Types ---

    /**
     * Register additional custom panel types.
     *
     * @param  array<class-string<AbstractStudioPanel>>  $types
     */
    public function panelTypes(array $types): static
    {
        $this->additionalPanelTypes = $types;

        return $this;
    }

    /**
     * @return array<class-string<AbstractStudioPanel>>
     */
    public function getAdditionalPanelTypes(): array
    {
        return $this->additionalPanelTypes;
    }

    // --- Condition Resolvers ---

    public function conditionResolver(string $key, callable $resolver, bool $reactive = false): static
    {
        ConditionEvaluator::registerResolver($key, $resolver, $reactive);

        return $this;
    }

    // --- Tenant Hook ---

    public function afterTenantCreated(\Closure $callback): static
    {
        $this->afterTenantCreatedCallback = $callback;

        return $this;
    }

    public function getAfterTenantCreatedCallback(): ?\Closure
    {
        return $this->afterTenantCreatedCallback;
    }

    // --- Static Hook: afterTenantCreated ---

    public static function afterTenantCreatedHook(\Closure $callback): void
    {
        static::$afterTenantCreatedHooks[] = $callback;
    }

    public static function fireAfterTenantCreated(Model $tenant): void
    {
        foreach (static::$afterTenantCreatedHooks as $hook) {
            $hook($tenant);
        }

        if ($callback = app(static::class)->getAfterTenantCreatedCallback()) {
            $callback($tenant);
        }
    }

    // --- Static Hook: afterCollectionCreated ---

    public static function afterCollectionCreated(\Closure $callback): void
    {
        static::$afterCollectionCreatedHooks[] = $callback;
    }

    public static function fireAfterCollectionCreated(StudioCollection $collection): void
    {
        foreach (static::$afterCollectionCreatedHooks as $hook) {
            $hook($collection);
        }
    }

    // --- Static Hook: afterFieldAdded ---

    public static function afterFieldAdded(\Closure $callback): void
    {
        static::$afterFieldAddedHooks[] = $callback;
    }

    public static function fireAfterFieldAdded(StudioField $field): void
    {
        foreach (static::$afterFieldAddedHooks as $hook) {
            $hook($field);
        }
    }

    // --- Static Hook: modifyFormSchema ---

    public static function modifyFormSchema(\Closure $callback): void
    {
        static::$modifyFormSchemaHooks[] = $callback;
    }

    /**
     * @param  array<mixed>  $schema
     * @return array<mixed>
     */
    public static function applyModifyFormSchema(array $schema, StudioCollection $collection): array
    {
        foreach (static::$modifyFormSchemaHooks as $hook) {
            $schema = $hook($schema, $collection);
        }

        return $schema;
    }

    // --- Static Hook: modifyTableColumns ---

    public static function modifyTableColumns(\Closure $callback): void
    {
        static::$modifyTableColumnsHooks[] = $callback;
    }

    /**
     * @param  array<mixed>  $columns
     * @return array<mixed>
     */
    public static function applyModifyTableColumns(array $columns, StudioCollection $collection): array
    {
        foreach (static::$modifyTableColumnsHooks as $hook) {
            $columns = $hook($columns, $collection);
        }

        return $columns;
    }

    // --- Static Hook: modifyQuery ---

    public static function modifyQuery(\Closure $callback): void
    {
        static::$modifyQueryHooks[] = $callback;
    }

    public static function applyModifyQuery(mixed $query): mixed
    {
        foreach (static::$modifyQueryHooks as $hook) {
            $query = $hook($query);
        }

        return $query;
    }

    // --- Reset All Hooks ---

    public static function resetHooks(): void
    {
        static::$afterTenantCreatedHooks = [];
        static::$afterCollectionCreatedHooks = [];
        static::$afterFieldAddedHooks = [];
        static::$modifyFormSchemaHooks = [];
        static::$modifyTableColumnsHooks = [];
        static::$modifyQueryHooks = [];
    }
}
