<?php

namespace Flexpik\FilamentStudio\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Flexpik\FilamentStudio\Concerns\InteractsWithPanelConfig;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Flexpik\FilamentStudio\Services\EavQueryBuilder;

class ListWidget extends TableWidget
{
    use InteractsWithPanelConfig;

    protected int|string|array $columnSpan = 'full';

    public function mount(StudioPanel $panel, array $variables = [], ?string $recordUuid = null): void
    {
        $this->mountInteractsWithPanelConfig($panel, $variables, $recordUuid);
    }

    public function table(Table $table): Table
    {
        $config = $this->resolvedConfig();
        $collectionId = $config['collection_id'] ?? null;

        if (! $collectionId) {
            return $table->columns([TextColumn::make('message')->default('No collection configured')]);
        }

        $collection = StudioCollection::find($collectionId);
        if (! $collection) {
            return $table->columns([TextColumn::make('message')->default('Collection not found')]);
        }

        $query = EavQueryBuilder::for($collection)->tenant($this->panel->tenant_id);

        if ($sortField = $config['sort_field'] ?? null) {
            $query->orderBy($sortField, $config['sort_direction'] ?? 'asc');
        }

        $limit = (int) ($config['limit'] ?? 10);
        $eloquentQuery = $query->toEloquentQuery()->limit($limit);

        $template = $config['display_template'] ?? '';
        preg_match_all('/\{\{(\w+)\}\}/', $template, $matches);
        $fieldNames = $matches[1];

        $columns = [];
        foreach ($fieldNames as $fieldName) {
            $columns[] = TextColumn::make($fieldName)->label(ucfirst($fieldName));
        }

        if (empty($columns)) {
            $columns[] = TextColumn::make('uuid')->label('ID');
        }

        return $table
            ->query(fn () => $eloquentQuery)
            ->columns($columns)
            ->paginated(false)
            ->heading($this->panel->header_visible ? $this->panel->header_label : null);
    }
}
