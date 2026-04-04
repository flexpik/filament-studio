<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;

class TagsFieldType extends AbstractFieldType
{
    public static string $key = 'tags';

    public static string $label = 'Tags';

    public static string $icon = 'heroicon-o-tag';

    public static EavCast $eavCast = EavCast::Json;

    public static string $category = 'structured';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('separator')->label('Separator')->default(',')
                ->placeholder(',')
                ->helperText('Character used to separate individual tags.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $tagsInput = TagsInput::make($this->field->column_name);

        if ($this->setting('separator')) {
            $tagsInput->separator($this->setting('separator'));
        }

        return $tagsInput;
    }

    public function toTableColumn(): ?Column
    {
        return TextColumn::make($this->field->column_name)
            ->badge()
            ->formatStateUsing(function (mixed $state): string {
                $values = is_string($state) ? (json_decode($state, true) ?? [$state]) : (array) $state;

                return collect($values)->implode(', ');
            })
            ->separator(', ');
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}
