<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;

class FileFieldType extends AbstractFieldType
{
    public static string $key = 'file';

    public static string $label = 'File Upload';

    public static string $icon = 'heroicon-o-paper-clip';

    public static EavCast $eavCast = EavCast::Text;

    public static string $category = 'file';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('disk')->label('Disk')->default('public')->placeholder('public')->helperText('Laravel filesystem disk for storing uploaded files.'),
            TextInput::make('directory')->label('Directory')->default('uploads')->placeholder('uploads')->helperText('Subdirectory within the disk for storing files.'),
            Select::make('visibility')->options(['public' => 'Public', 'private' => 'Private'])->default('public')->helperText('Public files are accessible via URL. Private files require signed URLs.'),
            TagsInput::make('accepted_types')->label('Accepted MIME Types')->placeholder('e.g. application/pdf')->helperText('Comma-separated MIME types. Use wildcards like image/* for any image.'),
            TextInput::make('max_size')->numeric()->label('Max Size (KB)')->default(10240)->placeholder('10240')->helperText('Maximum file size in kilobytes (10240 = 10 MB).'),
            Toggle::make('multiple')->label('Allow Multiple Files')->default(false)->helperText('Allow uploading multiple files at once.'),
            TextInput::make('min_files')->numeric()->label('Min Files')->placeholder('e.g. 1')->helperText('Minimum number of files required when multiple uploads is enabled.'),
            TextInput::make('max_files')->numeric()->label('Max Files')->placeholder('e.g. 5')->helperText('Maximum number of files allowed when multiple uploads is enabled.'),
            Toggle::make('enable_open')->label('Enable Open')->default(true)->helperText('Show a button to open/preview uploaded files.'),
            Toggle::make('enable_download')->label('Enable Download')->default(true)->helperText('Show a button to download uploaded files.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $upload = FileUpload::make($this->field->column_name);

        if ($this->setting('disk')) {
            $upload->disk($this->setting('disk'));
        }

        if ($this->setting('directory')) {
            $upload->directory($this->setting('directory'));
        }

        if ($this->setting('visibility')) {
            $upload->visibility($this->setting('visibility'));
        }

        $acceptedTypes = $this->setting('accepted_types');
        if (is_array($acceptedTypes) && ! empty($acceptedTypes)) {
            $upload->acceptedFileTypes($acceptedTypes);
        }

        if ($this->setting('max_size')) {
            $upload->maxSize((int) $this->setting('max_size'));
        }

        if ($this->setting('multiple')) {
            $upload->multiple();

            if ($this->setting('min_files')) {
                $upload->minFiles((int) $this->setting('min_files'));
            }

            if ($this->setting('max_files')) {
                $upload->maxFiles((int) $this->setting('max_files'));
            }
        }

        if ($this->setting('enable_open')) {
            $upload->openable();
        }

        if ($this->setting('enable_download')) {
            $upload->downloadable();
        }

        return $upload;
    }

    public function toTableColumn(): ?Column
    {
        return TextColumn::make($this->field->column_name)
            ->limit(30)
            ->icon('heroicon-o-paper-clip');
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}
