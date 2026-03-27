<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\TernaryFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;

class ToggleFieldType extends AbstractFieldType
{
    public static string $key = 'toggle';

    public static string $label = 'Toggle';

    public static string $icon = 'heroicon-o-bolt';

    public static EavCast $eavCast = EavCast::Boolean;

    public static string $category = 'boolean';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('on_color')->label('On Color')->default('success')
                ->placeholder('success')
                ->helperText('Color when toggled on. Options: primary, success, warning, danger, info, gray.'),
            TextInput::make('off_color')->label('Off Color')->default('danger')
                ->placeholder('danger')
                ->helperText('Color when toggled off.'),
            // on_label and off_label are stored for future use; Filament v5's Toggle component
            // does not expose onLabel()/offLabel() methods, so they cannot be applied at runtime.
            TextInput::make('on_label')->label('On Label')
                ->placeholder('e.g. Yes, Active, Enabled')
                ->helperText('Text displayed when the toggle is on.'),
            TextInput::make('off_label')->label('Off Label')
                ->placeholder('e.g. No, Inactive, Disabled')
                ->helperText('Text displayed when the toggle is off.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $toggle = Toggle::make($this->field->column_name);

        if ($this->setting('on_color')) {
            $toggle->onColor($this->setting('on_color'));
        }

        if ($this->setting('off_color')) {
            $toggle->offColor($this->setting('off_color'));
        }

        return $toggle;
    }

    public function toTableColumn(): ?Column
    {
        return IconColumn::make($this->field->column_name)
            ->boolean();
    }

    public function toFilter(): ?BaseFilter
    {
        return TernaryFilter::make($this->field->column_name)
            ->label($this->field->label ?? $this->field->column_name);
    }
}
