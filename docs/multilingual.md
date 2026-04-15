# Multilingual Content

Filament Studio supports opt-in multilingual content for translatable fields. When enabled, each field can store different values per locale, with automatic fallback to the default locale when a translation is missing.

## Enabling Multilingual Support

Enable multilingual globally via config:

```php
// config/filament-studio.php
'locales' => [
    'enabled' => true,
    'available' => ['en', 'fr', 'de'],
    'default' => 'en',
],
```

Or via environment variable:

```env
STUDIO_LOCALES_ENABLED=true
```

## Per-Collection Configuration

Each collection can define its own subset of available locales and a default locale. Configure this in the collection settings under the "Multilingual" section (only visible when `locales.enabled` is `true`).

```php
$collection->update([
    'supported_locales' => ['en', 'fr'],
    'default_locale' => 'en',
]);
```

If a collection has no `supported_locales` set, it inherits the global `locales.available` list.

## Translatable Fields

Individual fields are marked as translatable via the "Translatable" toggle in the field editor's behavior settings. Only fields where per-locale content makes sense should be translatable — for example, a "Title" or "Description" field, but not a "Price" or "Date" field.

When a field is translatable:
- Each locale stores its own value in `studio_values` with a `locale` column
- A unique constraint `[record_id, field_id, locale]` prevents duplicate entries
- Non-translatable fields always use the default locale, regardless of the active locale

## Locale Resolution

The `LocaleResolver` service determines the active locale using a priority chain:

| Priority | Source | Example |
|----------|--------|---------|
| 1 (highest) | `?locale=` query parameter | `?locale=fr` |
| 2 | `X-Locale` request header | `X-Locale: fr` |
| 3 | Session value | `studio_locale` session key |
| 4 | Collection default | `$collection->default_locale` |
| 5 (lowest) | Global default | `config('filament-studio.locales.default')` |

The first valid locale found in this chain is used. A locale is considered valid if it appears in the collection's `supported_locales` (or the global `available` list).

## Fallback Behavior

When a translatable field has no value for the requested locale, it falls back to the default locale. The `EavQueryBuilder::getRecordDataWithMeta()` method returns metadata about which fields fell back:

```php
$result = EavQueryBuilder::for($collection)
    ->locale('fr')
    ->getRecordDataWithMeta($record);

// $result['data'] => ['title' => 'Mon Titre', 'slug' => 'my-slug']
// $result['fallbacks'] => ['slug']  — slug fell back to the default locale
```

## Admin UI

### Locale Switcher

When multilingual is enabled and a collection has multiple locales, a locale switcher appears in the record edit and view pages. Clicking a locale button switches the form to display values for that locale. The selected locale persists in the session.

### Version History

The version history slide-over includes a locale switcher at the top. Translatable fields display the value for the selected locale with a locale badge (e.g. **EN**), and diffs compare values within the same locale. Non-translatable fields display normally regardless of the selected locale.

## EavQueryBuilder Locale Support

The query builder accepts a `locale()` fluent method that applies to all subsequent operations:

```php
// Read data in a specific locale
$data = EavQueryBuilder::for($collection)
    ->locale('fr')
    ->getRecordData($record);

// Create a record in a specific locale
$record = EavQueryBuilder::for($collection)
    ->locale('fr')
    ->create(['title' => 'Mon Titre']);

// Update a record in a specific locale
EavQueryBuilder::for($collection)
    ->locale('fr')
    ->update($record->id, ['title' => 'Titre Mis a Jour']);

// Get all locale data at once
$allData = EavQueryBuilder::for($collection)
    ->getAllLocaleData($record);
// => ['title' => ['en' => 'My Title', 'fr' => 'Mon Titre'], 'price' => 29.99]
```

The `getAllLocaleData()` method returns translatable fields as nested locale maps and non-translatable fields as plain values.

## REST API

All API endpoints support locale selection. See the [REST API documentation](api.md#multilingual-api-support) for details.

### Query Parameter

```bash
curl -H "X-Api-Key: your-key" \
     "https://your-app.com/api/studio/posts?locale=fr"
```

### Header

```bash
curl -H "X-Api-Key: your-key" \
     -H "X-Locale: fr" \
     "https://your-app.com/api/studio/posts"
```

### Single Locale Response

Responses include `_meta` with the active locale and any fields that fell back:

```json
{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "data": {
      "title": "Mon Titre",
      "slug": "my-slug",
      "price": 29.99
    },
    "created_by": 1,
    "updated_by": null,
    "created_at": "2025-01-15T10:30:00.000000Z",
    "updated_at": "2025-01-15T10:30:00.000000Z"
  },
  "_meta": {
    "locale": "fr",
    "fallbacks": ["slug"]
  }
}
```

### All Locales Response

Use `?all_locales=true` to get all translations in a single response:

```bash
curl -H "X-Api-Key: your-key" \
     "https://your-app.com/api/studio/posts/550e8400?all_locales=true"
```

```json
{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "data": {
      "title": {"en": "My Title", "fr": "Mon Titre"},
      "slug": {"en": "my-slug", "fr": "my-slug"},
      "price": 29.99
    },
    "created_by": 1,
    "updated_by": null,
    "created_at": "2025-01-15T10:30:00.000000Z",
    "updated_at": "2025-01-15T10:30:00.000000Z"
  }
}
```

Translatable fields appear as nested locale objects. Non-translatable fields remain plain values.

## OpenAPI Documentation

When multilingual is enabled and [Scramble](https://scramble.dedoc.co/) is installed, the auto-generated API documentation includes:

- `locale` query parameter with an enum dropdown of available locales
- `X-Locale` header parameter as an alternative locale selector
- `all_locales` boolean query parameter on GET single-record endpoints
- `_meta` object in response schemas with `locale` and `fallbacks` fields
- Locale availability hints in operation descriptions

## Field Label Translations

Field metadata (labels, placeholders, hints) can also be translated per locale using the `translations` JSON column on `StudioField`:

```php
$field->update([
    'translations' => [
        'fr' => ['label' => 'Titre', 'placeholder' => 'Entrez un titre'],
        'de' => ['label' => 'Titel', 'placeholder' => 'Titel eingeben'],
    ],
]);

// Resolve label for active locale
$label = $field->getTranslatedAttribute('label'); // "Titre" when locale is fr
```

If no translation exists for the active locale, the base attribute value is used.

## Version Snapshots

When versioning is enabled, snapshots capture all locale values for translatable fields:

```json
{
  "title": {"en": "My Title", "fr": "Mon Titre"},
  "slug": {"en": "my-slug"},
  "price": 29.99
}
```

Restoring a version writes back all locale rows for translatable fields.
