<?php

namespace Flexpik\FilamentStudio\Pages;

use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Widgets\WidgetConfiguration;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Flexpik\FilamentStudio\Panels\PanelTypeRegistry;
use Illuminate\Database\QueryException;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class StudioDashboardPage extends Page
{
    protected static ?string $slug = 'studio-dashboard/{dashboardSlug?}';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected string $view = 'filament-panels::pages.page';

    public ?StudioDashboard $dashboard = null;

    public string $dashboardSlug = '';

    public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'hasPermissionTo')) {
            return $user !== null;
        }

        $separator = config('filament-shield.permissions.separator', ':');

        try {
            return $user->hasPermissionTo("View{$separator}StudioDashboardPage");
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    public function mount(?string $dashboardSlug = null): void
    {
        try {
            $this->mountDashboard($dashboardSlug);
        } catch (QueryException) {
            // Tables may not exist yet
        }
    }

    protected function mountDashboard(?string $dashboardSlug): void
    {
        $tenantId = Filament::getTenant()?->getKey();

        if ($dashboardSlug) {
            $this->dashboardSlug = $dashboardSlug;
            $this->dashboard = StudioDashboard::query()
                ->forTenant($tenantId)
                ->where('slug', $dashboardSlug)
                ->firstOrFail();
        } else {
            $this->dashboard = StudioDashboard::query()
                ->forTenant($tenantId)
                ->ordered()
                ->first();
            $this->dashboardSlug = $this->dashboard !== null ? $this->dashboard->slug : '';
        }
    }

    public function getTitle(): string
    {
        return $this->dashboard !== null ? $this->dashboard->name : 'Dashboard';
    }

    /**
     * @return int|array<string, ?int>
     */
    public function getColumns(): int|array
    {
        return 12;
    }

    /**
     * @return array<class-string|WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        if (! $this->dashboard) {
            return [];
        }

        return $this->buildDashboardWidgets();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make($this->getColumns())
                    ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getWidgets())),
            ]);
    }

    /**
     * @return array<WidgetConfiguration>
     */
    protected function buildDashboardWidgets(): array
    {
        $registry = app(PanelTypeRegistry::class);

        $panels = StudioPanel::query()
            ->forDashboard($this->dashboard->id)
            ->get();

        $widgets = [];

        foreach ($panels as $panel) {
            if (! isset($registry->all()[$panel->panel_type])) {
                continue;
            }

            $panelType = $registry->get($panel->panel_type);
            $widgetClass = $panelType::$widgetClass;

            $widgets[] = $widgetClass::make(['panel' => $panel]);
        }

        return $widgets;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
