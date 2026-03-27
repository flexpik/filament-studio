<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;
use Illuminate\Support\HtmlString;

class CalloutFieldType extends AbstractFieldType
{
    public static string $key = 'callout';

    public static string $label = 'Callout';

    public static string $icon = 'heroicon-o-information-circle';

    public static EavCast $eavCast = EavCast::Text;

    public static string $category = 'presentation';

    public static function settingsSchema(): array
    {
        return [
            Textarea::make('content')->label('Callout Content')->required()->placeholder('e.g. Please review all fields before submitting.')->helperText('The message text displayed inside the callout box.'),
            Select::make('type')
                ->options(['info' => 'Info', 'warning' => 'Warning', 'danger' => 'Danger', 'success' => 'Success'])
                ->default('info')
                ->helperText('Visual style: info (blue), warning (yellow), danger (red), success (green).'),
            TextInput::make('icon')->label('Icon (Heroicon)')->placeholder('e.g. heroicon-o-exclamation-triangle')->helperText('Custom icon. Leave empty to use the default icon for the callout type.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $content = $this->setting('content', '');
        $type = $this->setting('type', 'info');

        $colorClass = match ($type) {
            'warning' => 'text-warning-600 dark:text-warning-400',
            'danger' => 'text-danger-600 dark:text-danger-400',
            'success' => 'text-success-600 dark:text-success-400',
            default => 'text-info-600 dark:text-info-400',
        };

        return Placeholder::make($this->field->column_name)
            ->label('')
            ->content(new HtmlString("<div class=\"{$colorClass} text-sm\">".e($content).'</div>'));
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
