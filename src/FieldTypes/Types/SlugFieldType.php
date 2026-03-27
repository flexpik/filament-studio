<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;
use Illuminate\Support\Str;

class SlugFieldType extends AbstractFieldType
{
    public static string $key = 'slug';

    public static string $label = 'Slug';

    public static string $icon = 'heroicon-o-link';

    public static EavCast $eavCast = EavCast::Text;

    public static string $category = 'text';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('source_field')
                ->label('Source Field (column_name)')
                ->placeholder('e.g. title, name')
                ->helperText('The field to auto-derive the slug from. Must be a text field that appears before this one.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $input = TextInput::make($this->field->column_name)
            ->prefix('/')
            ->live(onBlur: true);

        $sourceField = $this->setting('source_field');

        if ($sourceField) {
            $input->afterStateUpdated(function ($state, callable $set): void {
                if (empty($state)) {
                    return;
                }
                $set($this->field->column_name, Str::slug($state));
            });
        }

        return $input;
    }

    public function toTableColumn(): ?Column
    {
        return TextColumn::make($this->field->column_name)
            ->badge()
            ->color('gray');
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}
