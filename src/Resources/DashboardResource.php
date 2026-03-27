<?php

namespace Flexpik\FilamentStudio\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Flexpik\FilamentStudio\Models\StudioDashboard;
use Flexpik\FilamentStudio\Resources\DashboardResource\Pages\CreateDashboard;
use Flexpik\FilamentStudio\Resources\DashboardResource\Pages\EditDashboard;
use Flexpik\FilamentStudio\Resources\DashboardResource\Pages\ListDashboards;
use Flexpik\FilamentStudio\Resources\DashboardResource\RelationManagers\PanelsRelationManager;
use Illuminate\Database\Eloquent\Builder;

class DashboardResource extends Resource
{
    protected static ?string $model = StudioDashboard::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static string|\UnitEnum|null $navigationGroup = 'Studio';

    protected static ?string $navigationLabel = 'Dashboards';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(128),
            TextInput::make('slug')
                ->required()
                ->maxLength(64)
                ->alphaDash(),
            TextInput::make('icon')
                ->label('Icon')
                ->helperText('Heroicon name, e.g. heroicon-o-chart-pie'),
            TextInput::make('color')
                ->label('Color'),
            TextInput::make('auto_refresh_interval')
                ->label('Auto-Refresh Interval (seconds)')
                ->numeric()
                ->helperText('Leave empty to disable'),
            TextInput::make('sort_order')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->forTenant(Filament::getTenant()?->getKey()))
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug'),
                TextColumn::make('panels_count')
                    ->label('Panels')
                    ->counts('panels'),
                TextColumn::make('sort_order')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PanelsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDashboards::route('/'),
            'create' => CreateDashboard::route('/create'),
            'edit' => EditDashboard::route('/{record}/edit'),
        ];
    }
}
