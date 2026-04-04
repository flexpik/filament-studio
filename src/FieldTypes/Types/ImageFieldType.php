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

class ImageFieldType extends AbstractFieldType
{
    public static string $key = 'image';

    public static string $label = 'Image Upload';

    public static string $icon = 'heroicon-o-photo';

    public static EavCast $eavCast = EavCast::Text;

    public static string $category = 'file';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('disk')->label('Disk')->default('public')->placeholder('public')->helperText('Laravel filesystem disk for storing uploaded images.'),
            TextInput::make('directory')->label('Directory')->default('images')->placeholder('images')->helperText('Subdirectory within the disk for storing files.'),
            TextInput::make('max_size')->numeric()->label('Max Size (KB)')->default(10240)->placeholder('10240')->helperText('Maximum file size in kilobytes (10240 = 10 MB).'),
            TextInput::make('image_crop_aspect_ratio')->label('Crop Aspect Ratio (e.g. 16:9)')->placeholder('e.g. 16:9, 1:1, 4:3')->helperText('Force a specific crop aspect ratio. Leave empty for free cropping.'),
            TextInput::make('image_resize_target_width')->numeric()->label('Resize Target Width')->placeholder('e.g. 1920')->helperText('Resize uploaded images to this width (in pixels). Leave empty to keep original.'),
            TextInput::make('image_resize_target_height')->numeric()->label('Resize Target Height')->placeholder('e.g. 1080')->helperText('Resize uploaded images to this height (in pixels).'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $upload = FileUpload::make($this->field->column_name)
            ->image()
            ->disk($this->setting('disk', 'public'))
            ->directory($this->setting('directory', 'images'));

        if ($this->setting('max_size')) {
            $upload->maxSize((int) $this->setting('max_size'));
        }

        if ($this->setting('image_crop_aspect_ratio')) {
            $upload->imageCropAspectRatio($this->setting('image_crop_aspect_ratio'));
        }

        if ($this->setting('image_resize_target_width')) {
            $upload->imageResizeTargetWidth((string) $this->setting('image_resize_target_width'));
        }

        if ($this->setting('image_resize_target_height')) {
            $upload->imageResizeTargetHeight((string) $this->setting('image_resize_target_height'));
        }

        return $upload;
    }

    public function toTableColumn(): ?Column
    {
        $column = ImageColumn::make($this->field->column_name)->size(40);

        $column->disk($this->setting('disk', 'public'));

        return $column;
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}
