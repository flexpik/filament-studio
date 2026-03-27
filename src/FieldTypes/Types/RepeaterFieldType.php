<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;

class RepeaterFieldType extends AbstractFieldType
{
    public static string $key = 'repeater';

    public static string $label = 'Repeater';

    public static string $icon = 'heroicon-o-queue-list';

    public static EavCast $eavCast = EavCast::Json;

    public static string $category = 'structured';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('min_items')->numeric()->label('Min Items')->placeholder('e.g. 1')->helperText('Minimum number of items required.'),
            TextInput::make('max_items')->numeric()->label('Max Items')->placeholder('e.g. 10')->helperText('Maximum number of items allowed. Leave empty for unlimited.'),
            Toggle::make('collapsible')->label('Collapsible')->default(false)->helperText('Allow collapsing individual repeated items to save space.'),
            Toggle::make('reorderable')->label('Reorderable')->default(true)->helperText('Allow drag-and-drop reordering of repeated items.'),
            TextInput::make('columns')->numeric()->default(1)->label('Columns')->helperText('Number of form columns within each repeated item.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $repeater = Repeater::make($this->field->column_name)
            ->schema([
                TextInput::make('value')->label('Value'),
            ]);

        if ($this->setting('min_items')) {
            $repeater->minItems((int) $this->setting('min_items'));
        }

        if ($this->setting('max_items')) {
            $repeater->maxItems((int) $this->setting('max_items'));
        }

        if ($this->setting('collapsible')) {
            $repeater->collapsible();
        }

        if ($this->setting('reorderable', true)) {
            $repeater->reorderable();
        }

        if ($this->setting('columns')) {
            $repeater->columns((int) $this->setting('columns'));
        }

        return $repeater;
    }

    public function toTableColumn(): ?Column
    {
        return TextColumn::make($this->field->column_name)
            ->formatStateUsing(fn ($state) => is_array($state) ? count($state).' items' : '0 items')
            ->badge();
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}
