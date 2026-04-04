<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;

class AvatarFieldType extends AbstractFieldType
{
    public static string $key = 'avatar';

    public static string $label = 'Avatar';

    public static string $icon = 'heroicon-o-user-circle';

    public static EavCast $eavCast = EavCast::Text;

    public static string $category = 'file';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('disk')->label('Disk')->default('public')->placeholder('public')->helperText('Laravel filesystem disk for storing avatar images.'),
            TextInput::make('directory')->label('Directory')->default('avatars')->placeholder('avatars')->helperText('Subdirectory within the disk for storing avatars.'),
            TextInput::make('max_size')->numeric()->label('Max Size (KB)')->default(2048)->placeholder('2048')->helperText('Maximum file size in kilobytes (2048 = 2 MB).'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $upload = FileUpload::make($this->field->column_name)
            ->image()
            ->avatar()
            ->imageCropAspectRatio('1:1')
            ->imageResizeTargetWidth('256')
            ->imageResizeTargetHeight('256')
            ->disk($this->setting('disk', 'public'))
            ->directory($this->setting('directory', 'avatars'));

        if ($this->setting('max_size')) {
            $upload->maxSize((int) $this->setting('max_size'));
        }

        return $upload;
    }

    public function toTableColumn(): ?Column
    {
        $column = ImageColumn::make($this->field->column_name)->circular()->size(40);

        $column->disk($this->setting('disk', 'public'));

        return $column;
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}
