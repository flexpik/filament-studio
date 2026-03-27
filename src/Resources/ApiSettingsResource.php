<?php

namespace Flexpik\FilamentStudio\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Flexpik\FilamentStudio\Enums\ApiAction;
use Flexpik\FilamentStudio\Models\StudioApiKey;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Resources\ApiSettingsResource\Pages;
use Illuminate\Database\Eloquent\Builder;

class ApiSettingsResource extends Resource
{
    protected static ?string $model = StudioApiKey::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'API Keys';

    protected static string|\UnitEnum|null $navigationGroup = 'Studio';

    protected static ?int $navigationSort = 100;

    protected static ?string $slug = 'studio/api-keys';

    protected static ?string $modelLabel = 'API Key';

    protected static ?string $pluralModelLabel = 'API Keys';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('API Key Details')
                    ->description('Configure the name, status, and expiry for this API key.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Mobile App Key'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive keys will be rejected on all API requests.'),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->nullable()
                            ->helperText('Leave blank for a non-expiring key.'),
                    ])
                    ->columns(2),

                Section::make('Permissions')
                    ->description('Control which collections and actions this key can access.')
                    ->schema([
                        Forms\Components\Toggle::make('wildcard_access')
                            ->label('Full Access (Wildcard)')
                            ->helperText('Grant access to all collections and all actions.')
                            ->reactive()
                            ->default(false),

                        Forms\Components\Repeater::make('permission_entries')
                            ->label('Collection Permissions')
                            ->hidden(fn (Get $get) => (bool) $get('wildcard_access'))
                            ->schema([
                                Forms\Components\Select::make('collection_slug')
                                    ->label('Collection')
                                    ->options(fn () => StudioCollection::query()->forTenant(Filament::getTenant()?->getKey())->pluck('label', 'slug')->toArray())
                                    ->required()
                                    ->searchable(),
                                Forms\Components\CheckboxList::make('actions')
                                    ->label('Allowed Actions')
                                    ->options(ApiAction::asSelectOptions())
                                    ->columns(3),
                            ])
                            ->addActionLabel('Add Collection Permission')
                            ->collapsible()
                            ->itemLabel(fn (array $state) => $state['collection_slug'] ?? 'New Permission')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->forTenant(Filament::getTenant()?->getKey()))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiKeys::route('/'),
            'create' => Pages\CreateApiKey::route('/create'),
            'edit' => Pages\EditApiKey::route('/{record}/edit'),
        ];
    }
}
