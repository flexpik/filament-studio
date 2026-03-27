# Field Types

Filament Studio ships with **33 built-in field types** organized into 9 categories. Each field type defines how data is stored (via EAV cast), how it appears in forms, how it renders in tables, and how it can be filtered.

## Built-in Field Types

### Text

| Type | Key | Storage | Description |
|------|-----|---------|-------------|
| Text | `text` | `val_text` | Single-line text input. Supports subtypes: email, url, tel, numeric, password. |
| Textarea | `textarea` | `val_text` | Multi-line text input with configurable rows. |
| Rich Editor | `rich_editor` | `val_text` | WYSIWYG HTML editor powered by Tiptap. |
| Markdown | `markdown` | `val_text` | Markdown editor with live preview. |
| Password | `password` | `val_text` | Password input with masking. |
| Slug | `slug` | `val_text` | URL-friendly slug, auto-generated from another field. |
| Color | `color` | `val_text` | Color picker with hex value output. |
| Hidden | `hidden` | `val_text` | Hidden form field for storing computed or system values. |

### Numeric

| Type | Key | Storage | Description |
|------|-----|---------|-------------|
| Integer | `integer` | `val_integer` | Whole number input with optional min/max. |
| Decimal | `decimal` | `val_decimal` | Decimal number input with configurable precision. |
| Range | `range` | `val_integer` | Slider input with min, max, and step values. |

### Boolean

| Type | Key | Storage | Description |
|------|-----|---------|-------------|
| Checkbox | `checkbox` | `val_boolean` | Single checkbox for true/false values. |
| Toggle | `toggle` | `val_boolean` | Toggle switch for on/off states. |

### Selection

| Type | Key | Storage | Description |
|------|-----|---------|-------------|
| Select | `select` | `val_text` | Dropdown select with searchable options. |
| Multi-Select | `multi_select` | `val_json` | Multi-value dropdown stored as JSON array. |
| Radio | `radio` | `val_text` | Radio button group for single selection. |
| Checkbox List | `checkbox_list` | `val_json` | Checkbox group for multiple selections, stored as JSON array. |
| Tags | `tags` | `val_json` | Free-form tag input, stored as JSON array. |

### Date & Time

| Type | Key | Storage | Description |
|------|-----|---------|-------------|
| Date | `date` | `val_datetime` | Date picker (date only). |
| Time | `time` | `val_text` | Time picker (time only). |
| Datetime | `datetime` | `val_datetime` | Combined date and time picker. |

### File

| Type | Key | Storage | Description |
|------|-----|---------|-------------|
| File | `file` | `val_text` | General file upload. Path stored as text. |
| Image | `image` | `val_text` | Image upload with preview. |
| Avatar | `avatar` | `val_text` | Circular avatar image upload. |

### Relational

| Type | Key | Storage | Description |
|------|-----|---------|-------------|
| Belongs To | `belongs_to` | `val_text` | Single relationship to another collection's record. |
| Has Many | `has_many` | `val_json` | One-to-many relationship. |
| Belongs To Many | `belongs_to_many` | `val_json` | Many-to-many relationship. |

### Structured

| Type | Key | Storage | Description |
|------|-----|---------|-------------|
| Repeater | `repeater` | `val_json` | Repeating rows of sub-fields, stored as JSON. |
| Builder | `builder` | `val_json` | Block-based builder with multiple block types. |
| Key-Value | `key_value` | `val_json` | Key-value pair editor, stored as JSON object. |

### Presentation

| Type | Key | Storage | Description |
|------|-----|---------|-------------|
| Section Header | `section_header` | — | Groups subsequent fields into a labeled section. Not stored. |
| Divider | `divider` | — | Visual divider line between fields. Not stored. |
| Callout | `callout` | — | Informational notice displayed in the form. Not stored. |

## EAV Storage Columns

Every field type maps to one of six typed storage columns via the `EavCast` enum:

| EavCast | Column | PHP Type |
|---------|--------|----------|
| `Text` | `val_text` | `string` |
| `Integer` | `val_integer` | `int` |
| `Decimal` | `val_decimal` | `float` |
| `Boolean` | `val_boolean` | `bool` |
| `Datetime` | `val_datetime` | `Carbon` |
| `Json` | `val_json` | `array` |

This typed-column approach preserves native database sorting and indexing while maintaining a flexible schema.

## Field Configuration

Every field supports these common settings through the admin UI:

| Setting | Description |
|---------|-------------|
| `label` | Display name in forms and tables |
| `column_name` | Internal identifier (snake_case handle) |
| `is_required` | Whether the field must have a value |
| `is_unique` | Enforce unique values across the collection |
| `placeholder` | Placeholder text in form inputs |
| `hint` / `hint_icon` | Helper text and icon shown below the input |
| `default_value` | Pre-filled value for new records |
| `width` | Column span: Half, Full, or Expanded |
| `is_hidden_in_form` | Hide the field from create/edit forms |
| `is_hidden_in_table` | Hide the field from the list table |
| `is_filterable` | Allow filtering by this field |
| `is_disabled_on_create` | Disable during record creation |
| `is_disabled_on_edit` | Disable during record editing |
| `validation_rules` | Additional Laravel validation rules (JSON array) |
| `sort_order` | Display order within the collection |
| `settings` | Type-specific configuration (JSON) |

Each field type may define additional type-specific settings through its `settingsSchema()` method.

## Field Width

Fields support three width options via the `FieldWidth` enum:

- **Half** — Takes half the form width (1 column span)
- **Full** — Takes full width (2 column span)
- **Expanded** — Breaks out of the grid (full span)
