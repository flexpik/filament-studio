# REST API

Filament Studio can auto-generate a RESTful API for all your dynamic collections. The API supports full CRUD operations with API key authentication, per-collection permissions, and rate limiting.

## Enabling the API

Enable the API in your plugin registration:

```php
FilamentStudioPlugin::make()
    ->enableApi();
```

Or via environment variables:

```env
STUDIO_API_ENABLED=true
STUDIO_API_PREFIX=api/studio
STUDIO_API_RATE_LIMIT=60
```

## Authentication

All API requests require an `X-Api-Key` header:

```bash
curl -H "X-Api-Key: your-api-key-here" \
     https://your-app.com/api/studio/posts
```

API keys are managed through the Filament admin panel under **Studio > API Settings**.

### API Key Properties

| Property | Description |
|----------|-------------|
| `name` | Descriptive name for the key |
| `key` | The API key (shown once at creation, stored as SHA256 hash) |
| `permissions` | Per-collection action permissions |
| `is_active` | Whether the key is enabled |
| `expires_at` | Optional expiration date |
| `tenant_id` | Tenant scope (optional) |

### Permissions

API keys use granular permissions structured as collection-action pairs:

```json
{
  "posts": ["index", "show", "store"],
  "products": ["index", "show", "store", "update", "destroy"],
  "*": ["index", "show"]
}
```

The wildcard `*` grants the specified actions on all collections. Per-collection entries override the wildcard for that collection.

Available actions: `index`, `show`, `store`, `update`, `destroy`.

## Endpoints

All routes are prefixed with the configured API prefix (default: `/api/studio`).

### List Records

```
GET /api/studio/{collection_slug}
```

**Query Parameters:**

| Parameter | Default | Description |
|-----------|---------|-------------|
| `per_page` | `25` | Records per page (max 100) |
| `page` | `1` | Page number |

**Response:** Paginated collection with metadata.

```json
{
  "data": [
    {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "data": {
        "title": "My Post",
        "status": "published"
      },
      "created_by": 1,
      "updated_by": null,
      "created_at": "2025-01-15T10:30:00.000000Z",
      "updated_at": "2025-01-15T10:30:00.000000Z"
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 3, "per_page": 25, "total": 72 }
}
```

### Get Single Record

```
GET /api/studio/{collection_slug}/{uuid}
```

**Response:**

```json
{
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "data": {
      "title": "My Post",
      "status": "published"
    },
    "created_by": 1,
    "updated_by": null,
    "created_at": "2025-01-15T10:30:00.000000Z",
    "updated_at": "2025-01-15T10:30:00.000000Z"
  }
}
```

### Create Record

```
POST /api/studio/{collection_slug}
```

**Request Body:**

```json
{
  "data": {
    "title": "New Post",
    "status": "draft",
    "priority": 3
  }
}
```

**Response:** `201 Created` with the new record.

**Validation:**
- Required fields must be present
- Type validation based on EAV cast (string, integer, numeric, boolean, date, array)
- Custom validation rules from field definitions are applied

### Update Record

```
PUT /api/studio/{collection_slug}/{uuid}
```

**Request Body:** Same structure as create. Fields use `sometimes` validation — only include fields you want to update.

```json
{
  "data": {
    "status": "published"
  }
}
```

**Response:** `200 OK` with the updated record.

### Delete Record

```
DELETE /api/studio/{collection_slug}/{uuid}
```

**Response:** `204 No Content`.

If soft deletes are enabled on the collection, the record is soft-deleted rather than permanently removed.

## Rate Limiting

API requests are rate-limited per API key (or per IP if no key). The default is 60 requests per minute, configurable via:

```php
// config/filament-studio.php
'api' => [
    'rate_limit' => env('STUDIO_API_RATE_LIMIT', 120),
],
```

## OpenAPI Documentation

When the API is enabled and [Scramble](https://scramble.dedoc.co/) is installed, Filament Studio auto-generates OpenAPI documentation with:

- API Key security scheme (`X-Api-Key` header)
- Schema definitions for all endpoints
- Request/response examples

## Error Responses

| Status | Description |
|--------|-------------|
| `401 Unauthorized` | Missing or invalid API key |
| `403 Forbidden` | API key lacks permission for this collection/action |
| `404 Not Found` | Collection or record not found |
| `422 Unprocessable Entity` | Validation errors |
| `429 Too Many Requests` | Rate limit exceeded |
