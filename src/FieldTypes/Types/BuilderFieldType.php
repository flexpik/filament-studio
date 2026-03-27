<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;

class BuilderFieldType extends AbstractFieldType
{
    public static string $key = 'builder';

    public static string $label = 'Builder';

    public static string $icon = 'heroicon-o-cube';

    public static EavCast $eavCast = EavCast::Json;

    public static string $category = 'structured';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('max_items')->numeric()->label('Max Blocks')->placeholder('e.g. 20')->helperText('Maximum number of blocks allowed. Leave empty for unlimited.'),
            Toggle::make('collapsible')->label('Collapsible')->default(true)->helperText('Allow collapsing individual blocks to save space.'),
            Toggle::make('reorderable')->label('Reorderable')->default(true)->helperText('Allow drag-and-drop reordering of blocks.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $builder = Builder::make($this->field->column_name)
            ->blocks([
                Block::make('text')
                    ->label('Text Block')
                    ->schema([
                        TextInput::make('content')->label('Content'),
                    ]),
            ]);

        if ($this->setting('max_items')) {
            $builder->maxItems((int) $this->setting('max_items'));
        }

        if ($this->setting('collapsible', true)) {
            $builder->collapsible();
        }

        if ($this->setting('reorderable', true)) {
            $builder->reorderable();
        }

        return $builder;
    }

    public function toTableColumn(): ?Column
    {
        return TextColumn::make($this->field->column_name)
            ->formatStateUsing(fn ($state) => is_array($state) ? count($state).' blocks' : '0 blocks')
            ->badge();
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}
