<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;

class SectionHeaderFieldType extends AbstractFieldType
{
    public static string $key = 'section_header';

    public static string $label = 'Section Header';

    public static string $icon = 'heroicon-o-rectangle-group';

    public static EavCast $eavCast = EavCast::Text;

    public static string $category = 'presentation';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('section_label')->label('Section Label')->placeholder('e.g. Contact Information')->helperText('Heading text displayed above the section.'),
            TextInput::make('description')->label('Description')->placeholder('e.g. Enter the customer contact details below.')->helperText('Smaller text displayed below the section heading.'),
            TextInput::make('icon')->label('Icon (Heroicon)')->placeholder('e.g. heroicon-o-user')->helperText('Heroicon displayed next to the section heading.'),
            Toggle::make('collapsible')->label('Collapsible')->default(false)->helperText('Allow users to collapse this section.'),
            Toggle::make('collapsed')->label('Collapsed by Default')->default(false)->helperText('Start with this section collapsed by default.'),
            TextInput::make('columns')->numeric()->default(1)->label('Columns')->helperText('Number of form columns for fields within this section.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $section = Section::make($this->setting('section_label', ''));

        if ($this->setting('description')) {
            $section->description($this->setting('description'));
        }

        if ($this->setting('icon')) {
            $section->icon($this->setting('icon'));
        }

        if ($this->setting('collapsible')) {
            $section->collapsible();
        }

        if ($this->setting('collapsed')) {
            $section->collapsed();
        }

        if ($this->setting('columns')) {
            $section->columns((int) $this->setting('columns'));
        }

        return $section;
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
