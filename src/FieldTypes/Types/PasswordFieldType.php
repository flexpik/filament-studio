<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;

class PasswordFieldType extends AbstractFieldType
{
    public static string $key = 'password';

    public static string $label = 'Password';

    public static string $icon = 'heroicon-o-lock-closed';

    public static EavCast $eavCast = EavCast::Text;

    public static string $category = 'text';

    public static function settingsSchema(): array
    {
        return [
            Toggle::make('revealable')->label('Revealable')->default(true)->helperText('Allow users to toggle password visibility.'),
            Toggle::make('require_confirmation')->label('Require Confirmation')->default(false)->helperText('Adds a second input where users must re-type the password.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $input = TextInput::make($this->field->column_name)
            ->password()
            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? bcrypt($state) : null)
            ->dehydrated(fn (?string $state): bool => filled($state))
            ->formatStateUsing(fn (): ?string => null);

        if ($this->setting('revealable', true)) {
            $input->revealable();
        }

        if ($this->setting('require_confirmation')) {
            $input->confirmed();
        }

        return $input;
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
