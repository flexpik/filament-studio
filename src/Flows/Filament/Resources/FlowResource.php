<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Flows\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Flexpik\FilamentStudio\Flows\Enums\FlowStatus;
use Flexpik\FilamentStudio\Flows\Enums\LoggingMode;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\Pages;
use Flexpik\FilamentStudio\Flows\Filament\Resources\FlowResource\RelationManagers;
use Flexpik\FilamentStudio\Flows\Models\StudioFlow;
use Flexpik\FilamentStudio\Models\StudioApiKey;

class FlowResource extends Resource
{
    protected static ?string $model = StudioFlow::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'Studio Automations';

    protected static string|\UnitEnum|null $navigationGroup = 'Studio';

    protected static ?string $modelLabel = 'Flow';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('slug')->required()->alphaDash()->maxLength(255)->unique(ignoreRecord: true),
            Forms\Components\Textarea::make('description')->rows(2)->maxLength(65535),
            Forms\Components\TextInput::make('icon')->maxLength(255)->placeholder('heroicon-o-bolt'),
            Forms\Components\TextInput::make('color')->maxLength(255)->placeholder('#00BFA6'),
            Forms\Components\Select::make('status')->options(FlowStatus::class)->required()->default(FlowStatus::Inactive),
            Forms\Components\Select::make('logging_mode')->options(LoggingMode::class)->required()->default(LoggingMode::Full),
            Section::make('Webhook Security')
                ->collapsible()
                ->schema([
                    Forms\Components\Select::make('webhook_auth_mode')
                        ->label('Auth mode')
                        ->options([
                            'hmac' => 'HMAC signature',
                            'api_key' => 'API key',
                            'none' => 'Public (no auth)',
                        ])
                        ->default('hmac')
                        ->required()
                        ->live(),
                    Forms\Components\Select::make('webhook_allowed_studio_api_key_ids')
                        ->label('Allowed API keys')
                        ->multiple()
                        ->options(fn () => StudioApiKey::query()
                            ->where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray())
                        ->visible(fn (Get $get) => $get('webhook_auth_mode') === 'api_key'),
                    Forms\Components\Placeholder::make('public_mode_warning')
                        ->label('')
                        ->content('⚠️ Public mode: Any request without authentication will trigger this flow. Only use this for testing or with additional external security controls.')
                        ->visible(fn (Get $get) => $get('webhook_auth_mode') === 'none'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('slug')->searchable()->sortable()->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('status')->badge()->sortable(),
            Tables\Columns\TextColumn::make('logging_mode')->badge()->toggleable(),
            Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->since(),
        ])->filters([
            Tables\Filters\SelectFilter::make('status')->options(FlowStatus::class),
        ])->actions([
            EditAction::make(),
            Action::make('design')
                ->label('Design')
                ->icon('heroicon-o-squares-2x2')
                ->url(fn (StudioFlow $record) => self::getUrl('design', ['record' => $record])),
            Action::make('runs')
                ->label('Runs')
                ->icon('heroicon-o-clock')
                ->url(fn (StudioFlow $record) => self::getUrl('runs', ['record' => $record])),
        ])->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AuditLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFlows::route('/'),
            'create' => Pages\CreateFlow::route('/create'),
            'edit' => Pages\EditFlow::route('/{record}/edit'),
            'design' => Pages\DesignFlow::route('/{record}/design'),
            'runs' => Pages\ListFlowRuns::route('/{record}/runs'),
            'view-run' => Pages\ViewFlowRun::route('/{record}/runs/{runId}'),
        ];
    }
}
