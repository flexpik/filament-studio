<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;

class MarkdownFieldType extends AbstractFieldType
{
    public static string $key = 'markdown';

    public static string $label = 'Markdown Editor';

    public static string $icon = 'heroicon-o-hashtag';

    public static EavCast $eavCast = EavCast::Text;

    public static string $category = 'text';

    public static function settingsSchema(): array
    {
        return [
            TextInput::make('attachment_disk')->label('Attachment Disk')->default('public')->placeholder('public')->helperText('Filesystem disk for attachments dragged into the editor.'),
            TextInput::make('attachment_directory')->label('Attachment Directory')->default('markdown-uploads')->placeholder('markdown-uploads')->helperText('Subdirectory for storing markdown attachments.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $editor = MarkdownEditor::make($this->field->column_name);

        if ($this->setting('attachment_disk')) {
            $editor->fileAttachmentsDisk($this->setting('attachment_disk'));
        }

        if ($this->setting('attachment_directory')) {
            $editor->fileAttachmentsDirectory($this->setting('attachment_directory'));
        }

        return $editor;
    }

    public function toTableColumn(): ?Column
    {
        return TextColumn::make($this->field->column_name)
            ->markdown()
            ->limit(100)
            ->wrap();
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}
