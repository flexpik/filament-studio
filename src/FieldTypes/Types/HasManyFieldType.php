<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;

class HasManyFieldType extends AbstractFieldType
{
    public static string $key = 'has_many';

    public static string $label = 'Has Many';

    public static string $icon = 'heroicon-o-rectangle-stack';

    public static EavCast $eavCast = EavCast::Json;

    public static string $category = 'relational';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('related_collection')->label('Related Collection Slug')->required()->placeholder('e.g. comments')->helperText('The collection whose records belong to this one.'),
            TextInput::make('foreign_key_field')->label('Foreign Key Field in Related Collection')->placeholder('Auto-generated')->helperText('The field in the related collection that references this record.'),
            TextInput::make('display_field')->label('Display Field')->default('name')->placeholder('name')->helperText('Which field from the related collection to display in the table.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        return Placeholder::make($this->field->column_name)
            ->label($this->field->label ?? $this->field->column_name)
            ->content('Related records are managed from the '.($this->setting('related_collection') ?? 'related').' collection.');
    }

    public function toTableColumn(): ?Column
    {
        return TextColumn::make($this->field->column_name)
            ->label($this->field->label ?? $this->field->column_name)
            ->badge()
            ->formatStateUsing(fn ($state) => is_array($state) ? count($state).' records' : '0 records');
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}
