<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;
use Illuminate\Database\Eloquent\Builder;

class TextFieldType extends AbstractFieldType
{
    public static string $key = 'text';

    public static string $label = 'Text Input';

    public static string $icon = 'heroicon-o-bars-3-bottom-left';

    public static EavCast $eavCast = EavCast::Text;

    public static string $category = 'text';

    public static function settingsSchema(): array
    {
        return [
            Select::make('subtype')
                ->options([
                    'default' => 'Default',
                    'email' => 'Email',
                    'url' => 'URL',
                    'tel' => 'Telephone',
                    'numeric' => 'Numeric',
                    'password' => 'Password',
                ])
                ->default('default')
                ->helperText('Controls input validation and keyboard type on mobile devices.'),
            TextInput::make('prefix')->label('Prefix')->placeholder('e.g. $')->helperText('Text or symbol displayed before the input.'),
            TextInput::make('suffix')->label('Suffix')->placeholder('e.g. kg')->helperText('Text or symbol displayed after the input.'),
            TextInput::make('min')->numeric()->label('Min Length')->placeholder('0')->helperText('Minimum character length.'),
            TextInput::make('max')->numeric()->label('Max Length')->placeholder('255')->helperText('Maximum character length.'),
            TextInput::make('step')->numeric()->label('Step')->helperText('Increment step for numeric subtype.'),
            TextInput::make('mask')->label('Input Mask')->placeholder('e.g. (999) 999-9999')->helperText('Input mask pattern. Use 9 for digits, a for letters, * for any character.'),
            Toggle::make('live_on_blur')->label('Live on Blur')->default(false)->helperText('Sync value to server when the user leaves the field. Required for slug auto-generation.'),
            TextInput::make('autocomplete')->label('Autocomplete Attribute')->placeholder('e.g. email, name, off')->helperText('Browser autocomplete attribute. See MDN docs for valid values.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $input = TextInput::make($this->field->column_name);

        $subtype = $this->setting('subtype', 'default');

        $input = match ($subtype) {
            'email' => $input->email(),
            'url' => $input->url(),
            'tel' => $input->tel(),
            'numeric' => $this->applyNumericSettings($input),
            'password' => $input->password(),
            default => $input,
        };

        if ($this->setting('prefix')) {
            $input->prefix($this->setting('prefix'));
        }

        if ($this->setting('suffix')) {
            $input->suffix($this->setting('suffix'));
        }

        if ($this->setting('mask')) {
            $input->mask($this->setting('mask'));
        }

        if ($this->setting('live_on_blur')) {
            $input->live(onBlur: true);
        }

        if ($this->setting('autocomplete')) {
            $input->autocomplete($this->setting('autocomplete'));
        }

        if ($subtype !== 'numeric') {
            if ($this->setting('min')) {
                $input->minLength((int) $this->setting('min'));
            }

            if ($this->setting('max')) {
                $input->maxLength((int) $this->setting('max'));
            }
        }

        return $input;
    }

    public function toTableColumn(): ?Column
    {
        $column = TextColumn::make($this->field->column_name);

        $subtype = $this->setting('subtype', 'default');

        if ($subtype === 'email') {
            $column->icon('heroicon-o-envelope');
        }

        if ($subtype === 'url') {
            $column->icon('heroicon-o-link');
        }

        if ($subtype === 'password') {
            $column->formatStateUsing(fn () => '********');
        }

        $column->limit(50);

        return $column;
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

    protected function applyNumericSettings(TextInput $input): TextInput
    {
        $input->numeric();

        if ($this->setting('min') !== null) {
            $input->minValue((float) $this->setting('min'));
        }

        if ($this->setting('max') !== null) {
            $input->maxValue((float) $this->setting('max'));
        }

        if ($this->setting('step') !== null) {
            $input->step((float) $this->setting('step'));
        }

        return $input;
    }
}
