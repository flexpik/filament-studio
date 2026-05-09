<?php

declare(strict_types=1);

namespace Flexpik\FilamentStudio\Mcp\Resources;

use Flexpik\FilamentStudio\FieldTypes\FieldTypeRegistry;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Uri('studio://field-types')]
#[MimeType('application/json')]
#[Description('Catalog of all 33 field types. Each entry has key, label, category, and eav_cast. For full settings schema of one type, read studio://field-types/{key}.')]
class FieldTypeCatalogResource extends Resource
{
    public function handle(Request $request): Response
    {
        $registry = app(FieldTypeRegistry::class);

        $entries = [];
        foreach ($registry->all() as $key => $class) {
            $entries[] = [
                'key' => $key,
                'label' => $class::$label,
                'category' => $class::$category,
                'eav_cast' => $class::$eavCast->value,
                'icon' => $class::$icon ?? null,
            ];
        }

        usort($entries, fn ($a, $b) => $a['category'] <=> $b['category'] ?: $a['key'] <=> $b['key']);

        return Response::text(json_encode([
            'field_types' => $entries,
        ], JSON_PRETTY_PRINT));
    }
}
