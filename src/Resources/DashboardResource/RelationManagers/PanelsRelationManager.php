<?php

namespace Flexpik\FilamentStudio\Resources\DashboardResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Flexpik\FilamentStudio\Enums\PanelPlacement;
use Flexpik\FilamentStudio\Models\StudioPanel;
use Flexpik\FilamentStudio\Panels\PanelTypeRegistry;

class PanelsRelationManager extends RelationManager
{
    protected static string $relationship = 'panels';

    public function form(Schema $schema): Schema
    {
        $registry = app(PanelTypeRegistry::class);
        $typeOptions = collect($registry->forPlacement(PanelPlacement::Dashboard))
            ->mapWithKeys(fn (string $class, string $key) => [$key => $class::$label])
            ->toArray();

        return $schema->components([
            Section::make('Panel Type')->schema([
                Select::make('panel_type')
                    ->label('Type')
                    ->options($typeOptions)
                    ->required()
                    ->live(),
            ]),
            Section::make('Header')->schema([
                Toggle::make('header_visible')
                    ->label('Show Header')
                    ->default(true),
                TextInput::make('header_label')
                    ->label('Label'),
                TextInput::make('header_icon')
                    ->label('Icon'),
                TextInput::make('header_color')
                    ->label('Color'),
                TextInput::make('header_note')
                    ->label('Note'),
            ]),
            Section::make('Configuration')
                ->statePath('config')
                ->schema(function (Get $get) use ($registry) {
                    $panelType = $get('panel_type');
                    if (! $panelType) {
                        return [];
                    }

                    return $registry->configSchema($panelType);
                }),
            Section::make('Grid Size')->schema([
                TextInput::make('grid_col_span')
                    ->label('Column Span (1-12)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(12)
                    ->default(6),
                TextInput::make('grid_row_span')
                    ->label('Row Span (1-8)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(8)
                    ->default(4),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('panel_type')->label('Type')->badge(),
                TextColumn::make('header_label')->label('Label'),
                TextColumn::make('grid_col_span')->label('Columns'),
                TextColumn::make('grid_order')->label('Order')->sortable(),
            ])
            ->defaultSort('grid_order')
            ->actions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, StudioPanel $record): array {
                        $data['config'] = $record->merged_config;

                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['tenant_id'] = Filament::getTenant()?->getKey();
                        $data['dashboard_id'] = $this->getOwnerRecord()->getKey();
                        $data['placement'] = PanelPlacement::Dashboard;
                        $data['grid_order'] = $this->getOwnerRecord()->panels()->max('grid_order') + 1;

                        return $data;
                    }),
            ])
            ->reorderable('grid_order');
    }
}
