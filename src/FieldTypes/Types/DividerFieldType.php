<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;
use Illuminate\Support\HtmlString;

class DividerFieldType extends AbstractFieldType
{
    public static string $key = 'divider';

    public static string $label = 'Divider';

    public static string $icon = 'heroicon-o-minus';

    public static EavCast $eavCast = EavCast::Text;

    public static string $category = 'presentation';

    public static function settingsSchema(): array
    {
        return [];
    }

    public function toFilamentComponent(): Component
    {
        return Placeholder::make($this->field->column_name)
            ->label('')
            ->content(new HtmlString('<hr class="border-gray-200 dark:border-gray-700">'))
            ->columnSpanFull();
    }

    public function toTableColumn(): ?Column
    {
        return null;
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}
