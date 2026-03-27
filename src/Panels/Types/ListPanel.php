<?php

namespace Flexpik\FilamentStudio\Panels\Types;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Facades\Filament;
use Flexpik\FilamentStudio\Models\StudioCollection;
use Flexpik\FilamentStudio\Models\StudioField;
use Flexpik\FilamentStudio\Panels\AbstractStudioPanel;
use Flexpik\FilamentStudio\Widgets\ListWidget;

class ListPanel extends AbstractStudioPanel
{
    public static string $key = 'list';

    public static string $label = 'List';

    public static string $icon = 'heroicon-o-list-bullet';

    public static string $description = 'Display a filtered, sorted list of records';

    public static string $widgetClass = ListWidget::class;

    public static function configSchema(): array
    {
        return [
            Select::make('collection_id')
                ->label('Collection')
                ->options(fn () => StudioCollection::query()->forTenant(Filament::getTenant()?->getKey())->pluck('label', 'id'))
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('sort_field', null)),
            TextInput::make('display_template')
                ->label('Display Template')
                ->helperText('Use {{field_name}} tokens. e.g., {{name}} — {{status}}')
                ->required(),
            Select::make('sort_field')
                ->label('Sort By')
                ->options(function (Get $get) {
                    $collectionId = $get('collection_id');
                    if (! $collectionId) {
                        return [];
                    }

                    return StudioField::query()
                        ->where('collection_id', $collectionId)
                        ->pluck('label', 'column_name');
                }),
            Select::make('sort_direction')
                ->label('Sort Direction')
                ->options(['asc' => 'Ascending', 'desc' => 'Descending'])
                ->default('asc'),
            TextInput::make('limit')
                ->label('Max Records')
                ->numeric()
                ->default(10),
            Toggle::make('enable_inline_edit')
                ->label('Enable Inline Edit')
                ->helperText('Opens record edit form in a slide-over')
                ->default(false),
        ];
    }
}
