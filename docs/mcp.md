# MCP Server

Filament Studio ships a built-in [Model Context Protocol](https://modelcontextprotocol.io/) (MCP) server. Connect any MCP-compatible AI assistant (Claude, Cursor, Windsurf, etc.) and let it manage your data model — create collections, define fields, query records, build dashboards — all through natural language.

## Enabling

MCP is disabled by default. Enable it in your environment:

```env
STUDIO_MCP_ENABLED=true
```

Or publish and edit the config:

```bash
php artisan vendor:publish --tag="filament-studio-config"
```

```php
// config/filament-studio.php
'mcp' => [
    'enabled' => env('STUDIO_MCP_ENABLED', false),

    'http' => [
        'enabled' => env('STUDIO_MCP_HTTP_ENABLED', true),
        'prefix'  => env('STUDIO_MCP_HTTP_PREFIX', 'ai/studio'),
    ],

    'stdio' => [
        'enabled' => env('STUDIO_MCP_STDIO_ENABLED', true),
        'handle'  => env('STUDIO_MCP_STDIO_HANDLE', 'studio'),
    ],
],
```

## Authentication

The MCP server authenticates every request with a **Studio API Key**. Create one under **Studio → API Settings** in your admin panel — enable **Wildcard Access** to grant the key permission over all collections.

For stdio transport, pass the key as an environment variable:

```env
STUDIO_API_KEY=your-plaintext-api-key
```

For HTTP transport, pass it as a header on each request:

```
Authorization: Bearer your-plaintext-api-key
```

> The key is stored as a SHA-256 hash. The plaintext is only shown once at creation time.

### Key Scoping

API keys can be **global** (`tenant_id = null`, wildcard access) or **tenant-scoped** (`tenant_id` set to a specific tenant). The MCP server inherits this scope — all tool calls from a tenant-scoped key automatically filter to that tenant.

## Connecting to Claude Code

Add an `mcp.json` file to your project's `.claude/` directory:

```json
{
  "mcpServers": {
    "filament-studio": {
      "type": "stdio",
      "command": "php",
      "args": ["artisan", "mcp:start", "studio"],
      "env": {
        "STUDIO_API_KEY": "your-plaintext-api-key",
        "STUDIO_MCP_ENABLED": "true"
      }
    }
  }
}
```

If your app runs inside Docker:

```json
{
  "mcpServers": {
    "filament-studio": {
      "type": "stdio",
      "command": "docker",
      "args": [
        "exec", "-i",
        "-w", "/var/www/html/your-app",
        "-e", "STUDIO_API_KEY=your-plaintext-api-key",
        "-e", "STUDIO_MCP_ENABLED=true",
        "your-php-container",
        "php", "artisan", "mcp:start", "studio"
      ]
    }
  }
}
```

Restart Claude Code after saving the file. The `filament-studio` server will appear in the MCP panel.

## HTTP Transport

When HTTP is enabled, the server mounts at the configured prefix (default `ai/studio`). Use this for remote connections or Streamable HTTP clients:

```
POST https://your-app.com/ai/studio
Authorization: Bearer your-plaintext-api-key
Content-Type: application/json
```

The HTTP endpoint is protected by the `studio-mcp` rate limiter (default 120 req/min) in addition to API key authentication.

## Resources

Resources are read-only reference documents the AI should read before using tools.

| URI | Description |
|-----|-------------|
| `studio://info` | Server version, tenant context, enabled features, and limits |
| `studio://field-types` | Catalog of all 33 built-in field types with EAV cast and categories |
| `studio://field-types/{key}` | Full schema for a specific field type including its settings |
| `studio://panel-types` | Catalog of all 9 built-in panel types |
| `studio://panel-types/{key}` | Full config schema for a specific panel type |
| `studio://operators` | All 23 filter operators grouped by compatible EAV cast |

**Recommended workflow:** Read `studio://info`, `studio://field-types`, `studio://panel-types`, and `studio://operators` before running any tools. This gives the AI the full catalog of what's available.

## Tools

### Collections

| Tool | Description |
|------|-------------|
| `studio_list_collections` | List collections. Filters: `is_singleton`, `is_hidden`, `api_enabled`, `name_search`. Paginated. |
| `studio_get_collection` | Fetch full collection definition (fields, options, settings) by slug. |
| `studio_create_collection` | Create a collection. Optional: slug, label, icon, description, flags, inline `fields[]`. |
| `studio_update_collection` | Update collection meta (name, label, icon, description, flags). Slug is immutable. |
| `studio_preview_delete_collection` | Preview deletion impact and obtain a `confirm_token`. |
| `studio_delete_collection` | Delete a collection. Requires `confirm_token` from preview. |

### Fields

| Tool | Description |
|------|-------------|
| `studio_create_field` | Add a field. Required: `collection_slug`, `column_name`, `field_type`. Optional: label, settings, inline `options[]`. |
| `studio_update_field` | Update field metadata. `collection_slug` + `column_name` identify the field. |
| `studio_reorder_fields` | Reorder fields by providing the full ordered list of `column_name`s. |
| `studio_set_field_options` | Bulk-replace the option list for select/multi-select/radio/checkbox-list fields. |
| `studio_preview_delete_field` | Returns value count, dependent saved filters, and a `confirm_token`. |
| `studio_delete_field` | Delete a field. Requires `confirm_token` from preview. |

### Records

| Tool | Description |
|------|-------------|
| `studio_query_records` | Query records with filter tree, sort, pagination, or aggregate functions. |
| `studio_get_record` | Fetch a single record by UUID. Pass `all_locales=true` for all locale variants. |
| `studio_create_record` | Create a record. `data` is a `{field: value}` map validated against collection field definitions. |
| `studio_update_record` | Partial update by UUID — only fields present in `data` are touched. |
| `studio_delete_record` | Delete by UUID. Soft-deletes when the collection has soft deletes enabled. Pass `force=true` to hard-delete. |

### Dashboards

| Tool | Description |
|------|-------------|
| `studio_list_dashboards` | List dashboards with their panels. |
| `studio_get_dashboard` | Get a dashboard by slug, including panels. |
| `studio_create_dashboard` | Create a dashboard. Optional: slug, icon, color, `auto_refresh_interval` (seconds, min 5). |
| `studio_update_dashboard` | Update dashboard meta. Panels are managed separately via panel tools. |
| `studio_preview_delete_dashboard` | Preview deletion and obtain a `confirm_token`. |
| `studio_delete_dashboard` | Delete a dashboard. Requires `confirm_token` from preview. |

### Panels

| Tool | Description |
|------|-------------|
| `studio_create_panel` | Create a panel. Required: `dashboard_slug`, `panel_type`, `placement`. See `studio://panel-types/{key}` for the config schema. |
| `studio_update_panel` | Update placement, grid, header, config, or sort order. `panel_type` is immutable. |
| `studio_reorder_panels` | Reorder panels within a dashboard. Updates both `grid_order` and `sort_order`. |
| `studio_delete_panel` | Delete a panel by id. No confirm token required. |

### Saved Filters

| Tool | Description |
|------|-------------|
| `studio_list_saved_filters` | List saved filters for a collection. |
| `studio_save_filter` | Create or update a saved filter. Omit `id` to create; provide `id` to update. |
| `studio_delete_saved_filter` | Delete a saved filter by id. |

### API Keys

| Tool | Description |
|------|-------------|
| `studio_list_api_keys` | List API keys. Raw secrets are never included. |
| `studio_get_api_key` | Fetch an API key by id (metadata only). |
| `studio_create_api_key` | Create a new API key. **The plaintext secret is returned once in this response — store it immediately.** |
| `studio_revoke_api_key` | Revoke a key by setting `is_active=false`. |

## Confirm Tokens (Two-Step Deletes)

Destructive operations — deleting collections, fields, and dashboards — require a two-step flow to prevent accidental data loss:

1. Call the `preview_delete` tool. It returns an impact summary and a short-lived `confirm_token`.
2. Pass the `confirm_token` to the delete tool within the TTL window (default 5 minutes).

```
studio_preview_delete_collection → { impact: {...}, confirm_token: "abc123", expires_at: "..." }
studio_delete_collection         ← { confirm_token: "abc123" }
```

The token is scoped to the specific operation and target — it cannot be reused for a different resource. Configure the TTL:

```env
STUDIO_MCP_CONFIRM_TOKEN_TTL=300
```

## Querying Records

`studio_query_records` accepts the same `FilterGroup` tree used by the REST API and the Filament filter builder:

```json
{
  "collection_slug": "posts",
  "tenant_id": "t1",
  "filter": {
    "operator": "AND",
    "rules": [
      { "field": "status", "operator": "eq", "value": "published" },
      { "field": "views",  "operator": "gte", "value": 100 }
    ]
  },
  "sort": [{ "field": "created_at", "direction": "desc" }],
  "per_page": 25,
  "page": 1
}
```

Available operators are documented in `studio://operators`. Nesting depth is limited by `STUDIO_MCP_QUERY_MAX_FILTER_DEPTH` (default 5).

## Rate Limiting

HTTP requests are rate-limited per API key. Configure the limit:

```env
STUDIO_MCP_HTTP_RATE_LIMIT=120
```

Stdio transport has no rate limiting — it runs as a local process under your control.

## Limits

| Config | Env variable | Default | Description |
|--------|-------------|---------|-------------|
| `mcp.limits.query_max_per_page` | `STUDIO_MCP_QUERY_MAX_PER_PAGE` | `100` | Maximum `per_page` for record queries |
| `mcp.limits.query_max_filter_depth` | `STUDIO_MCP_QUERY_MAX_FILTER_DEPTH` | `5` | Maximum nesting depth for filter trees |
| `mcp.limits.create_collection_max_fields` | `STUDIO_MCP_CREATE_COLLECTION_MAX_FIELDS` | `50` | Maximum inline fields when creating a collection |

## Logging

MCP requests and errors are logged to the configured channel:

```env
STUDIO_MCP_LOG_CHANNEL=stack
STUDIO_MCP_LOG_REQUESTS=true
STUDIO_MCP_LOG_ERRORS=true
```

Set `STUDIO_MCP_LOG_REQUESTS=false` in production to reduce log volume.
