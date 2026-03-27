<?php

namespace Flexpik\FilamentStudio\FieldTypes\Types;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Flexpik\FilamentStudio\Enums\EavCast;
use Flexpik\FilamentStudio\FieldTypes\AbstractFieldType;

class RichEditorFieldType extends AbstractFieldType
{
    public static string $key = 'rich_editor';

    public static string $label = 'Rich Editor';

    public static string $icon = 'heroicon-o-document-magnifying-glass';

    public static EavCast $eavCast = EavCast::Json;

    public static string $category = 'text';

    public static function settingsSchema(): array
    {
        return [
            Select::make('storage_format')
                ->options(['html' => 'HTML', 'json' => 'JSON'])
                ->default('html')
                ->helperText('HTML is widely compatible. JSON preserves editor structure for re-editing.'),
            TagsInput::make('toolbar_buttons')
                ->label('Toolbar Buttons')
                ->placeholder('e.g. bold, italic, h2')
                ->helperText('Comma-separated list of toolbar buttons to show. Leave empty for all buttons.'),
            TextInput::make('attachment_disk')->label('Attachment Disk')->default('public')->placeholder('public')->helperText('Filesystem disk for inline attachments (images, files).'),
            TextInput::make('attachment_directory')->label('Attachment Directory')->default('rich-uploads')->placeholder('rich-uploads')->helperText('Subdirectory for storing inline attachments.'),
        ];
    }

    public function toFilamentComponent(): Component
    {
        $editor = RichEditor::make($this->field->column_name);

        $toolbarButtons = $this->setting('toolbar_buttons');
        if (is_array($toolbarButtons) && ! empty($toolbarButtons)) {
            $editor->toolbarButtons($toolbarButtons);
        }

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
            ->html()
            ->limit(100)
            ->wrap();
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}
