<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;
use Illuminate\Database\Eloquent\Builder;

class TextareaFieldType extends AbstractFieldType
{
    public static string $key = 'textarea';

    public static string $label = 'Textarea';

    public static string $icon = 'heroicon-o-document-text';

    public static EavCast $eavCast = EavCast::Text;

    public static string $category = 'text';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('rows')->numeric()->default(3)->label('Rows')->helperText('Number of visible text rows. Affects initial height.'),
            TextInput::make('min')->numeric()->label('Min Length')->placeholder('0')->helperText('Minimum character length.'),
            TextInput::make('max')->numeric()->label('Max Length')->placeholder('65535')->helperText('Maximum character length.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $textarea = Textarea::make($this->field->column_name);

        if ($this->setting('rows')) {
            $textarea->rows((int) $this->setting('rows'));
        }

        if ($this->setting('min')) {
            $textarea->minLength((int) $this->setting('min'));
        }

        if ($this->setting('max')) {
            $textarea->maxLength((int) $this->setting('max'));
        }

        return $textarea;
    }

    public function toTableColumn(): ?Column
    {
        return TextColumn::make($this->field->column_name)
            ->limit(80)
            ->wrap();
    }

    public function toFilter(): ?BaseFilter
    {
        return Filter::make($this->field->column_name)
            ->form([
                TextInput::make('value')->label($this->field->label ?? $this->field->column_name),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query->when(
                    $data['value'],
                    fn (Builder $query, string $value): Builder => $query->where($this->field->column_name, 'like', "%{$value}%"),
                );
            });
    }
}
